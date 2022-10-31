<?php

namespace codemonauts\its\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\models\FieldLayout;
use yii\db\ActiveQueryInterface;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * @property int $id ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $statuses Statuses
 * @property int|null $fieldLayoutId Field layout ID
 * @property FieldLayout $fieldLayout Field layout
 *
 * @mixin SoftDeleteBehavior
 */
class IssueType extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%its_issuetypes}}';
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
