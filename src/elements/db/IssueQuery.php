<?php

namespace codemonauts\its\elements\db;

use codemonauts\its\elements\Issue;
use codemonauts\its\IssueTrackingSystem;
use codemonauts\its\models\IssueType;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use yii\base\InvalidConfigException;

/**
 * @method Issue[]|array all($db = null)
 * @method Issue|null one($db = null)
 */
class IssueQuery extends ElementQuery
{
    public string|array|null $state = null;

    public ?string $subject = null;

    public mixed $assigneeId = null;

    public mixed $reporterId = null;

    public mixed $typeId = null;

    /**
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function state(string|array|null $value): self
    {
        $this->state = $value;

        return $this;
    }

    /**
     * @param string|null $value The property value
     *
     * @return static self reference
     */
    public function subject(?string $value): self
    {
        $this->subject = $value;

        return $this;
    }

    /**
     * @param User|null $value The property value
     *
     * @return static self reference
     */
    public function assignee(?User $value): self
    {
        $this->assigneeId = $value?->id;

        return $this;
    }

    /**
     * @param int|int[] $value The property value
     *
     * @return static self reference
     */
    public function assigneeId(array|int $value): self
    {
        $this->assigneeId = $value;

        return $this;
    }

    /**
     * @param User|null $value The property value
     *
     * @return static self reference
     */
    public function reporter(?User $value): self
    {
        $this->reporterId = $value?->id;

        return $this;
    }

    /**
     * @param int|int[] $value The property value
     *
     * @return static self reference
     */
    public function reporterId(array|int $value): self
    {
        $this->reporterId = $value;

        return $this;
    }

    /**
     * @param mixed $value The property value
     *
     * @return static self reference
     * @throws \codemonauts\its\exceptions\IssueTypeNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function type(mixed $value): self
    {
        if (Db::normalizeParam($value, function ($item) {
            if (is_string($item)) {
                $item = IssueTrackingSystem::$plugin->getIssues()->getIssueTypeByHandle($item);
            }
            return $item instanceof IssueType ? $item->id : null;
        })) {
            $this->typeId = $value;
        } else {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%its_issuetypes}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        }

        return $this;
    }

    /**
     * @param int|int[] $value The property value
     *
     * @return static self reference
     */
    public function typeId(array|int $value): self
    {
        $this->typeId = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function beforePrepare(): bool
    {
        $this->normalizeUserIds();

        $this->joinElementTable('its_issues');

        $this->query->select([
            'its_issues.subject',
            'its_issues.state',
            'its_issues.typeId',
            'its_issues.reporterId',
            'its_issues.assigneeId',
        ]);

        if ($this->assigneeId) {
            $this->subQuery->andWhere(['its_issues.assigneeId' => $this->assigneeId]);
        }

        if ($this->reporterId) {
            $this->subQuery->andWhere(['its_issues.reporterId' => $this->reporterId]);
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(['its_issues.typeId' => $this->typeId]);
        }

        if ($this->state) {
            $this->subQuery->andWhere(['its_issues.state' => $this->state]);
        }

        if ($this->subject) {
            $this->subQuery->andWhere(['its_issues.subject' => $this->subject]);
        }

        return parent::beforePrepare();
    }

    /**
     * Normalizes the assigneeId and reporterId params to an array of IDs or null
     *
     * @throws InvalidConfigException
     */
    private function normalizeUserIds(): void
    {
        if ($this->assigneeId !== null) {
            if (is_numeric($this->assigneeId)) {
                $this->assigneeId = [$this->assigneeId];
            }
            if (!is_array($this->assigneeId) || !ArrayHelper::isNumeric($this->assigneeId)) {
                throw new InvalidConfigException();
            }
        }

        if ($this->reporterId !== null) {
            if (is_numeric($this->reporterId)) {
                $this->reporterId = [$this->reporterId];
            }
            if (!is_array($this->reporterId) || !ArrayHelper::isNumeric($this->reporterId)) {
                throw new InvalidConfigException();
            }
        }
    }
}
