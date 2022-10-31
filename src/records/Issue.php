<?php

namespace codemonauts\its\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\User;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id ID
 * @property string $subject Subject
 * @property string $state State
 * @property int $typeId Type ID
 * @property int|null $reporterId Reporter ID
 * @property int|null $assigneeId Assignee ID
 * @property Element $element Element
 * @property IssueType $type Type
 * @property User $assignee Assignee
 * @property User $reporter Reporter
 */
class Issue extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%its_issues}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(IssueType::class, ['id' => 'typeId']);
    }

    public function getAssignee(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'assigneeId']);
    }

    public function getReporter(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'reporterId']);
    }
}
