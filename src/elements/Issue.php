<?php

namespace codemonauts\its\elements;

use codemonauts\its\elements\conditions\IssueCondition;
use codemonauts\its\elements\db\IssueQuery;
use codemonauts\its\exceptions\IssueTypeNotFoundException;
use codemonauts\its\IssueTrackingSystem;
use codemonauts\its\models\IssueType;
use codemonauts\its\records\Issue as IssueRecord;
use codemonauts\its\services\History;
use Craft;
use craft\base\Element;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
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
     * @var string|null State of the issue
     */
    public ?string $state = null;

    /**
     * @var bool Whether the issue was deleted along with its issue type
     */
    public bool $deletedWithIssueType = false;

    /**
     * @var int|null Reporter (User) of the issue
     */
    private ?int $_reporterId = null;

    /**
     * @var User|null|false
     */
    private User|false|null $_reporter = null;

    /**
     * @var int|null Assignee (User) of the issue
     */
    private ?int $_assigneeId = null;

    /**
     * @var User|null|false
     */
    private User|false|null $_assignee = null;

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
        return false;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'assigneeId';
        $names[] = 'reporterId';
        $names[] = 'typeId';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'assignee';
        $names[] = 'reporter';
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

        if (IssueTrackingSystem::$settings->allIssuesAsSource) {
            $sources[] = [
                'key' => '*',
                'label' => Craft::t('its', 'All Issues'),
            ];
        }

        if (IssueTrackingSystem::$settings->myIssuesAsSource) {
            $sources[] = [
                'key' => 'my-issues',
                'label' => Craft::t('its', 'My Issues'),
                'criteria' => [
                    'assigneeId' => Craft::$app->getUser()->getId(),
                ],
            ];
        }

        $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();

        foreach ($issueTypes as $issueType) {
            $sources[] = [
                'key' => 'type:' . $issueType->uid,
                'label' => Craft::t('its', $issueType->name),
                'criteria' => [
                    'typeId' => $issueType->id,
                ],
            ];
        }

        return $sources;
    }

    /**
     * @return \codemonauts\its\models\IssueType
     * @throws \codemonauts\its\exceptions\IssueTypeNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function getType(): IssueType
    {
        if (!isset($this->_typeId)) {
            $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();
            $this->_typeId = $issueTypes[0]->id;
        }

        return IssueTrackingSystem::$plugin->getIssues()->getIssueTypeById($this->_typeId);
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
        } catch (IssueTypeNotFoundException) {
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

    public function getAssigneeId(): ?int
    {
        return $this->_assigneeId;
    }

    public function setAssigneeId(array|int|string|null $assigneeId): void
    {
        if ($assigneeId === '') {
            $assigneeId = null;
        }

        if (is_array($assigneeId)) {
            $this->_assigneeId = reset($assigneeId) ?: null;
        } else {
            $this->_assigneeId = $assigneeId;
        }

        $this->_assignee = null;
    }

    public function getAssignee(): ?User
    {
        if (!isset($this->_assignee)) {
            if (!$this->getAssigneeId()) {
                return null;
            }

            if (($this->_assignee = Craft::$app->getUsers()->getUserById($this->getAssigneeId())) === null) {
                $this->_assignee = false;
            }
        }

        return $this->_assignee ?: null;
    }

    public function setAssignee(?User $assignee = null): void
    {
        $this->_assignee = $assignee;
        $this->setAssigneeId($assignee?->id);
    }

    public function getReporterId(): ?int
    {
        return $this->_reporterId;
    }

    public function setReporterId(array|int|string|null $reporterId): void
    {
        if ($reporterId === '') {
            $reporterId = null;
        }

        if (is_array($reporterId)) {
            $this->_reporterId = reset($reporterId) ?: null;
        } else {
            $this->_reporterId = $reporterId;
        }

        $this->_reporter = null;
    }

    public function getReporter(): ?User
    {
        if (!isset($this->_reporter)) {
            if (!$this->getReporterId()) {
                return null;
            }

            if (($this->_reporter = Craft::$app->getUsers()->getUserById($this->getReporterId())) === null) {
                $this->_reporter = false;
            }
        }

        return $this->_reporter ?: null;
    }

    public function setReporter(?User $reporter = null): void
    {
        $this->_reporter = $reporter;
        $this->setReporterId($reporter?->id);
    }

    /**
     * @inheritdoc
     */
    public function hasRevisions(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function defineTableAttributes(): array
    {
        return [
            'subject' => Craft::t('its', 'Subject'),
            'reporter' => Craft::t('its', 'Reporter'),
            'assignee' => Craft::t('its', 'Assignee'),
            'dateCreated' => Craft::t('its', 'Date Created'),
            'dateUpdated' => Craft::t('its', 'Date Updated'),
            'durationUpdated' => Craft::t('its', 'Last Update'),
            'state' => Craft::t('its', 'State'),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'subject',
            'reporter',
            'assignee',
            'dateCreated',
            'durationUpdated',
        ];
    }

    /**
     * @inheritDoc
     */
    public static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('its', 'Issue ID'),
                'orderBy' => 'id',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('its', 'Subject'),
                'orderBy' => 'subject',
                'defaultDir' => 'asc',
            ],
            [
                'label' => Craft::t('its', 'Reporter'),
                'orderBy' => 'reporterId',
                'defaultDir' => 'asc',
            ],
            [
                'label' => Craft::t('its', 'Assignee'),
                'orderBy' => 'assigneeId',
                'defaultDir' => 'asc',
            ],
            [
                'label' => Craft::t('its', 'Date Created'),
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('its', 'Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'assignee':
                $assignee = $this->getAssignee();
                $button = Html::a(Craft::t('its', 'Take Issue'), UrlHelper::cpUrl('its/issue/take/' . $this->id), ['class' => 'btn small']);
                return $assignee ? Cp::elementHtml($assignee) . $button : $button;

            case 'reporter':
                $reporter = $this->getReporter();
                return $reporter ? Cp::elementHtml($reporter) : '';

            case 'durationUpdated':
                $diff = $this->dateUpdated?->diff(DateTimeHelper::now());
                return IssueTrackingSystem::$settings->useShortHumanDuration ? \codemonauts\its\helpers\DateTimeHelper::shortHumanDuration($diff) : DateTimeHelper::humanDuration($diff);

            case 'state':
                return '<span class="its-badge-' . $this->getType()->handle . '-' . $this->state . '">' . $this->getStatusLabel() . '</span>';

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
        if ($source === '*' || 'my-issues') {
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
        if (!$this->getReporterId()) {
            $this->setReporterId(Craft::$app->getUser()->getId());
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
                    Craft::$app->getRevisions()->createRevision($currentIssue, $currentIssue->getReporterId(), $revisionNotes);
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
        $rules[] = [['state'], 'string'];
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
                throw new InvalidConfigException('Invalid issue ID ' . $this->id);
            }
        } else {
            $record = new IssueRecord();
            $record->id = (int)$this->id;
        }

        $record->subject = $this->subject;
        $record->state = $this->state;
        $record->typeId = $this->getTypeId();
        $record->assigneeId = $this->getAssigneeId();
        $record->reporterId = $this->getReporterId();

        $dirtyAttributes = array_keys($record->getDirtyAttributes());

        $record->save(false);

        $this->setDirtyAttributes($dirtyAttributes);

        $event = $this->firstSave ? History::EVENT_HISTORY_ISSUE_CREATED : History::EVENT_HISTORY_ISSUE_UPDATED;

        if (!$this->getIsDraft() && $this->getIsCanonical()) {
            IssueTrackingSystem::$plugin->getHistory()->addEvent($event, $this, Craft::$app->getUser()->getIdentity());
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        parent::setAttributes($values, $safeOnly);

        if (isset($values['assignee'])) {
            $this->setAssigneeId($values['assignee']);
        }

        if (isset($values['reporter'])) {
            $this->setAssigneeId($values['reporter']);
        }
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

    public function getStatusLabel()
    {
        $statuses = $this->getType()->statuses;

        $state = ArrayHelper::where($statuses, 1, $this->state, false, false);

        return $state[0][0] ?? '';
    }

    /**
     * @inheritdoc
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(IssueCondition::class, [static::class]);
    }
}
