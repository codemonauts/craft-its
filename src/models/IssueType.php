<?php

namespace codemonauts\its\models;

use codemonauts\its\elements\Issue;
use codemonauts\its\records\IssueType as IssueTypeRecord;
use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * @mixin FieldLayoutBehavior
 */
class IssueType extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var array Statuses
     */
    public array $statuses = [];

    /**
     * @var int|null Field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * Returns the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->handle ?: static::class;
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Issue::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Craft::t('its', 'Name'),
            'handle' => Craft::t('its', 'Handle'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['dateDeleted']];
        $rules[] = [['handle'], UniqueValidator::class, 'targetClass' => IssueTypeRecord::class, 'targetAttribute' => ['handle'], 'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),];
        $rules[] = [['fieldLayout'], 'validateFieldLayout'];

        return $rules;
    }

    public function validateFieldLayout(): void
    {
        $fieldLayout = $this->getFieldLayout();
        if (!$fieldLayout->validate()) {
            $this->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl();
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'statuses' => $this->statuses,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}
