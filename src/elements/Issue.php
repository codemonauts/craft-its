<?php

namespace codemonauts\its\elements;

use codemonauts\its\elements\db\IssueQuery;
use codemonauts\its\IssueTrackingSystem;
use codemonauts\its\models\IssueType;
use codemonauts\its\records\Issue as IssueRecord;
use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use yii\base\InvalidConfigException;

class Issue extends Element
{
    /**
     * @var string Subject of the issue
     */
    public string $subject = '';

    /**
     * @var string|null Status of the issue
     */
    public ?string $status = null;

    /**
     * @var bool Whether the issue was deleted along with its issue type
     */
    public bool $deletedWithIssueType = false;

    /**
     * @var int|null Owner (User) of the issue
     */
    private ?int $_creatorId = null;

    /**
     * @var User|null|false
     */
    private User|false|null $_creator = null;

    /**
     * @var int|null Owner (User) of the issue
     */
    private ?int $_ownerId = null;

    /**
     * @var User|null|false
     */
    private User|false|null $_owner = null;

    /**
     * @var int|null Type ID
     */
    private ?int $_typeId = null;

    /**
     * @var int|null Type ID
     */
    private ?int $_oldTypeId = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->_oldTypeId = $this->_typeId;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('its', 'Issue');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('its', 'issue');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('its', 'Issues');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('its', 'issues');
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'ownerId';
        $names[] = 'creatorId';
        $names[] = 'typeId';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'owner';
        $names[] = 'creator';
        $names[] = 'type';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function cpEditUrl(): string
    {
        return UrlHelper::cpUrl('its/issue/' . $this->canonicalId);
    }

    /**
     * @inerhitdoc
     */
    public function canView(User $user): bool
    {
        return true;
    }

    /**
     * @inerhitdoc
     */
    public function canSave(User $user): bool
    {
        return true;
    }

    /**
     * @inerhitdoc
     */
    public function canDelete(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return \codemonauts\its\elements\db\IssueQuery
     */
    public static function find(): IssueQuery
    {
        return new IssueQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        $sources = [];

        $sources[] = [
            'key' => '*',
            'label' => Craft::t('its', 'All issues'),
        ];

        $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();

        foreach ($issueTypes as $issueType) {
            $source = [
                'key' => 'type:' . $issueType->uid,
                'label' => Craft::t('its', $issueType->name),
                'criteria' => [
                    'typeId' => $issueType->id,
                ],
            ];

            $sources[] = $source;
        }

        return $sources;
    }

    public function getType(): IssueType
    {
        if (!isset($this->_typeId)) {
            $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();
            $this->_typeId = $issueTypes[0]->id;
        }

        $issueType = IssueTrackingSystem::$plugin->getIssues()->getIssueTypeById($this->_typeId);
        if (!$issueType) {
            throw new InvalidConfigException("Issue has no issue types");
        }

        return $issueType;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if (($fieldLayout = parent::getFieldLayout()) !== null) {
            return $fieldLayout;
        }

        try {
            $issueType = $this->getType();
        } catch (InvalidConfigException) {
            return null;
        }
        return $issueType->getFieldLayout();
    }

    public function getTypeId(): int
    {
        return $this->getType()->id;
    }

    public function setTypeId(int $typeId): void
    {
        $this->_typeId = $typeId;
        $this->fieldLayoutId = null;
    }

    public function getOwnerId(): ?int
    {
        return $this->_ownerId;
    }

    public function setOwnerId(array|int|string|null $ownerId): void
    {
        if ($ownerId === '') {
            $ownerId = null;
        }

        if (is_array($ownerId)) {
            $this->_ownerId = reset($ownerId) ?: null;
        } else {
            $this->_ownerId = $ownerId;
        }

        $this->_owner = null;
    }

    public function getOwner(): ?User
    {
        if (!isset($this->_owner)) {
            if (!$this->getOwnerId()) {
                return null;
            }

            if (($this->_owner = Craft::$app->getUsers()->getUserById($this->getOwnerId())) === null) {
                $this->_owner = false;
            }
        }

        return $this->_owner ?: null;
    }

    public function setOwner(?User $owner = null): void
    {
        $this->_owner = $owner;
        $this->setOwnerId($owner?->id);
    }

    public function getCreatorId(): ?int
    {
        return $this->_creatorId;
    }

    public function setCreatorId(array|int|string|null $creatorId): void
    {
        if ($creatorId === '') {
            $creatorId = null;
        }

        if (is_array($creatorId)) {
            $this->_creatorId = reset($creatorId) ?: null;
        } else {
            $this->_creatorId = $creatorId;
        }

        $this->_creator = null;
    }

    public function getCreator(): ?User
    {
        if (!isset($this->_creator)) {
            if (!$this->getCreatorId()) {
                return null;
            }

            if (($this->_creator = Craft::$app->getUsers()->getUserById($this->getCreatorId())) === null) {
                $this->_creator = false;
            }
        }

        return $this->_creator ?: null;
    }

    public function setCreator(?User $creator = null): void
    {
        $this->_creator = $creator;
        $this->setCreatorId($creator?->id);
    }

    /**
     * @inheritdoc
     */
    public function hasRevisions(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'owner':
                $owner = $this->getOwner();
                return $owner ? Cp::elementHtml($owner) : '';

            case 'creator':
                $creator = $this->getCreator();
                return $creator ? Cp::elementHtml($creator) : '';

            case 'type':
                try {
                    return Html::encode(Craft::t('its', $this->getType()->name));
                } catch (InvalidConfigException) {
                    return Craft::t('app', 'Unknown');
                }
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritDoc
     */
    protected static function defineFieldLayouts(string $source): array
    {
        if ($source === '*') {
            $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();
        } else {
            preg_match('/^type:(.+)$/', $source, $matches) &&
            $issueType = IssueTrackingSystem::$plugin->getIssues()->getIssueTypeByUid($matches[1]);
            $issueTypes = [$issueType];
        }

        $fieldLayouts = [];
        foreach ($issueTypes as $issueType) {
            $fieldLayouts[] = $issueType->getFieldLayout();
        }

        return $fieldLayouts;
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        if (!$this->getCreatorId()) {
            $this->setCreatorId(Craft::$app->getUser()->getId());
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if ($this->shouldSaveRevision()) {
            $hasRevisions = self::find()
                ->revisionOf($this)
                ->site('*')
                ->status(null)
                ->exists();
            if (!$hasRevisions) {
                $currentIssue = self::find()
                    ->id($this->id)
                    ->site('*')
                    ->status(null)
                    ->one();

                if ($currentIssue) {
                    $revisionNotes = 'Revision from ' . Craft::$app->getFormatter()->asDatetime($currentIssue->dateUpdated);
                    Craft::$app->getRevisions()->createRevision($currentIssue, $currentIssue->getCreatorId(), $revisionNotes);
                }
            }
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl("its/issues");
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['subject'], 'required', 'on' => self::SCENARIO_LIVE];
        $rules[] = [['typeId'], 'integer'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = IssueRecord::findOne($this->id);

            if (!$record) {
                throw new InvalidConfigException('Invalid issue ID '.$this->id);
            }
        } else {
            $record = new IssueRecord();
            $record->id = (int)$this->id;
        }

        $record->subject = $this->subject;
        $record->status = $this->status;
        $record->typeId = $this->getTypeId();
        $record->ownerId = $this->getOwnerId();
        $record->creatorId = $this->getCreatorId();

        $dirtyAttributes = array_keys($record->getDirtyAttributes());

        $record->save(false);

        $this->setDirtyAttributes($dirtyAttributes);

        parent::afterSave($isNew);
    }

    private function shouldSaveRevision(): bool
    {
        return (
            $this->id &&
            !$this->resaving &&
            !$this->getIsDraft() &&
            !$this->getIsRevision()
        );
    }
}
