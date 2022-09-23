<?php

namespace codemonauts\its\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\User;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id ID
 * @property string $subject Subject
 * @property string $status Status
 * @property int $typeId Type ID
 * @property int|null $creatorId Creator ID
 * @property int|null $ownerId Owner ID
 * @property Element $element Element
 * @property IssueType $type Type
 * @property User $owner Owner
 * @property User $creator Creator
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

    public function getOwner(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'ownerId']);
    }

    public function getCreator(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'creatorId']);
    }
}
