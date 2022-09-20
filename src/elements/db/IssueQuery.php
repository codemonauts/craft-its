<?php

namespace codemonauts\its\elements\db;

use codemonauts\its\elements\Issue;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

/**
 * Class IssueQuery
 *
 * @method Issue[]|array all($db = null)
 * @method Issue|null one($db = null)
 */
class IssueQuery extends ElementQuery
{
    public string|array|null $status = null;

    public ?string $subject = null;

    public mixed $ownerId = null;

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
        $this->ownerId = $value;

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
     * @inheritDoc
     */
    protected function beforePrepare(): bool
    {
        $this->normalizeOwnerId();

        $this->joinElementTable('its_issues');

        $this->query->select([
            'its_issues.subject',
            'its_issues.status',
            'its_issues.ownerId',
        ]);

        if (isset($this->ownerId)) {
            if (!$this->ownerId) {
                throw new QueryAbortedException();
            }
            $this->subQuery->andWhere(['its_issues.ownerId' => $this->ownerId]);
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
     * Normalizes the ownerId param to an array of IDs or null
     *
     * @throws InvalidConfigException
     */
    private function normalizeOwnerId(): void
    {
        if ($this->ownerId === null) {
            return;
        }
        if (is_numeric($this->ownerId)) {
            $this->ownerId = [$this->ownerId];
        }
        if (!is_array($this->ownerId) || !ArrayHelper::isNumeric($this->ownerId)) {
            throw new InvalidConfigException();
        }
    }
}
