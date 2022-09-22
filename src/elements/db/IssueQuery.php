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
    public string|array|null $status = null;

    public ?string $subject = null;

    public mixed $ownerId = null;

    public mixed $creatorId = null;

    public mixed $typeId = null;

    /**
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function status(string|array|null $value): self
    {
        $this->status = $value;

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
    public function owner(?User $value): self
    {
        $this->ownerId = $value?->id;

        return $this;
    }

    /**
     * @param int|int[] $value The property value
     *
     * @return static self reference
     */
    public function ownerId(array|int $value): self
    {
        $this->ownerId = $value;

        return $this;
    }

    /**
     * @param User|null $value The property value
     *
     * @return static self reference
     */
    public function creator(?User $value): self
    {
        $this->creatorId = $value?->id;

        return $this;
    }

    /**
     * @param int|int[] $value The property value
     *
     * @return static self reference
     */
    public function creatorId(array|int $value): self
    {
        $this->creatorId = $value;

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
        if (Db::normalizeParam($value, function($item) {
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
            'its_issues.status',
            'its_issues.typeId',
            'its_issues.creatorId',
            'its_issues.ownerId',
        ]);

        if ($this->ownerId) {
            $this->subQuery->andWhere(['its_issues.ownerId' => $this->ownerId]);
        }

        if ($this->creatorId) {
            $this->subQuery->andWhere(['its_issues.creatorId' => $this->creatorId]);
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(['its_issues.typeId' => $this->typeId]);
        }

        if ($this->status) {
            $this->subQuery->andWhere(['its_issues.status' => $this->status]);
        }

        if ($this->subject) {
            $this->subQuery->andWhere(['its_issues.subject' => $this->subject]);
        }

        return parent::beforePrepare();
    }

    /**
     * Normalizes the ownerId and creatorId params to an array of IDs or null
     *
     * @throws InvalidConfigException
     */
    private function normalizeUserIds(): void
    {
        if ($this->ownerId !== null) {
            if (is_numeric($this->ownerId)) {
                $this->ownerId = [$this->ownerId];
            }
            if (!is_array($this->ownerId) || !ArrayHelper::isNumeric($this->ownerId)) {
                throw new InvalidConfigException();
            }
        }

        if ($this->creatorId !== null) {
            if (is_numeric($this->creatorId)) {
                $this->creatorId = [$this->creatorId];
            }
            if (!is_array($this->creatorId) || !ArrayHelper::isNumeric($this->creatorId)) {
                throw new InvalidConfigException();
            }
        }
    }
}
