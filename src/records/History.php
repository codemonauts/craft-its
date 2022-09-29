<?php

namespace codemonauts\its\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\User;
use yii\db\ActiveQueryInterface;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * @property int $id ID
 * @property int|null $issueId Issue ID
 * @property string $event Event handle
 * @property string $initiatorName Initiator name
 * @property int|null $initiatorId Initiator ID
 * @property string $data Additional data
 * @property User $initiator Initiator
 *
 * @mixin SoftDeleteBehavior
 */
class History extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%its_history}}';
    }

    public function getInitiator(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'initiatorId']);
    }
}
