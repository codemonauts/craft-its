<?php

namespace codemonauts\its\services;

use codemonauts\its\elements\Issue;
use codemonauts\its\exceptions\IssueTypeNotFoundException;
use codemonauts\its\models\IssueType;
use codemonauts\its\records\IssueType as IssueTypeRecord;
use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\Queue;
use craft\i18n\Translation;
use craft\models\FieldLayout;
use craft\queue\jobs\ResaveElements;
use craft\helpers\StringHelper;
use Throwable;
use yii\base\Component;

class Issues extends Component
{
    public function init()
    {
        Craft::$app->getProjectConfig()
            ->onAdd('issueTypes.{uid}', [$this, 'handleChangedIssueType'])
            ->onUpdate('issueTypes.{uid}', [$this, 'handleChangedIssueType'])
            ->onRemove('issueTypes.{uid}', [$this, 'handleDeletedIssueType']);
    }

    public function getIssueTypeById(int $id): IssueType
    {
        $issueData = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'fieldLayoutId',
                'uid',
            ])
            ->from(['{{%its_issuetypes}}'])
            ->where(['dateDeleted' => null, 'id' => $id])
            ->one();

        if (!$issueData) {
            throw new IssueTypeNotFoundException('Could not find issue with ID ' . $id);
        }

        return new IssueType($issueData);
    }

    public function getIssueTypeByHandle(string $handle): IssueType
    {
        $issueData = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'fieldLayoutId',
                'uid',
            ])
            ->from(['{{%its_issuetypes}}'])
            ->where(['dateDeleted' => null, 'handle' => $handle])
            ->one();

        if (!$issueData) {
            throw new IssueTypeNotFoundException('Could not find issue with handle "' . $handle . '"');
        }

        return new IssueType($issueData);
    }

    public function getAllIssueTypes(): array
    {
        $issueTypes = [];

        $results = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'fieldLayoutId',
                'uid',
            ])
            ->from(['{{%its_issuetypes}}'])
            ->where(['dateDeleted' => null])
            ->all();

        foreach ($results as $attributes) {
            $issueTypes[] = new IssueType($attributes);
        }

        return $issueTypes;
    }

    public function saveIssueType(IssueType $issueType, bool $runValidation = true): bool
    {
        $isNew = !$issueType->id;

        if ($runValidation && !$issueType->validate()) {
            return false;
        }

        if ($isNew) {
            $issueType->uid = StringHelper::UUID();
        }

        $configData = $issueType->getConfig();
        Craft::$app->getProjectConfig()->set('issueTypes.' . $issueType->uid, $configData, "Save issue type “{$issueType->handle}”");

        if ($isNew) {
            $issueType->id = Db::idByUid('{{%its_issuetypes}}', $issueType->uid);
        }

        return true;
    }

    public function handleChangedIssueType(ConfigEvent $event): void
    {
        $issueTypeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();
        ProjectConfigHelper::ensureAllSectionsProcessed();

        $issueTypeRecord = $this->getIssueTypeRecord($issueTypeUid, true);

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $isNewIssueType = $issueTypeRecord->getIsNewRecord();

            $issueTypeRecord->name = $data['name'];
            $issueTypeRecord->handle = $data['handle'];
            $issueTypeRecord->uid = $issueTypeUid;

            if (!empty($data['fieldLayouts'])) {
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $issueTypeRecord->fieldLayoutId;
                $layout->type = Issue::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout, false);
                $issueTypeRecord->fieldLayoutId = $layout->id;
            } else if ($issueTypeRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($issueTypeRecord->fieldLayoutId);
                $issueTypeRecord->fieldLayoutId = null;
            }

            $resaveIssues = (
                $issueTypeRecord->handle !== $issueTypeRecord->getOldAttribute('handle') ||
                $issueTypeRecord->fieldLayoutId != $issueTypeRecord->getOldAttribute('fieldLayoutId')
            );

            if ($wasTrashed = (bool)$issueTypeRecord->dateDeleted) {
                $issueTypeRecord->restore();
                $resaveIssues = true;
            } else {
                $issueTypeRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        if ($wasTrashed) {
            $issues = Issue::find()
                ->typeId($issueTypeRecord->id)
                ->drafts(null)
                ->draftOf(false)
                ->status(null)
                ->trashed()
                ->site('*')
                ->unique()
                ->andWhere(['its_issues.deletedWithIssueType' => true])
                ->all();
            Craft::$app->getElements()->restoreElements($issues);
        }

        if (!$isNewIssueType && $resaveIssues) {
            Queue::push(new ResaveElements([
                'description' => Translation::prep('its', 'Resaving {type} issues', [
                    'type' => $issueTypeRecord->name,
                ]),
                'elementType' => Issue::class,
                'criteria' => [
                    'typeId' => $issueTypeRecord->id,
                    'siteId' => '*',
                    'unique' => true,
                    'status' => null,
                ],
            ]));
        }

        // Invalidate issue caches
        Craft::$app->getElements()->invalidateCachesForElementType(Issue::class);
    }

    public function deleteIssueTypeById(int $issueTypeId): bool
    {
        $issueType = $this->getIssueTypeById($issueTypeId);

        return $this->deleteIssueType($issueType);
    }

    public function deleteIssueType(IssueType $issueType): bool
    {
        Craft::$app->getProjectConfig()->remove('issueTypes.' . $issueType->uid, "Delete the “{$issueType->handle}” issue type");

        return true;
    }

    public function handleDeletedIssueType(ConfigEvent $event): void
    {
        $elementsService = Craft::$app->getElements();

        $uid = $event->tokenMatches[0];
        $issueTypeRecord = $this->getIssueTypeRecord($uid);

        if (!$issueTypeRecord->id) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $issueQuery = Issue::find()
                ->typeId($issueTypeRecord->id)
                ->status(null)
                ->drafts(null)
                ->draftOf(false);

            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                foreach (Db::each($issueQuery->siteId($siteId)) as $issue) {
                    $issue->deletedWithIssueType = true;
                    $elementsService->deleteElement($issue);
                }
            }

            if ($issueTypeRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($issueTypeRecord->fieldLayoutId);
            }

            Craft::$app->getDb()->createCommand()
                ->softDelete('{{%its_issuetypes}}', ['id' => $issueTypeRecord->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        Craft::$app->getElements()->invalidateCachesForElementType(Issue::class);
    }

    private function getIssueTypeRecord(string $uid, bool $withTrashed = false): IssueTypeRecord
    {
        $query = $withTrashed ? IssueTypeRecord::findWithTrashed() : IssueTypeRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new IssueTypeRecord();
    }
}
