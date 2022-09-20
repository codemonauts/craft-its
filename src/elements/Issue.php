<?php
namespace codemonauts\its\elements;

use codemonauts\its\elements\db\IssueQuery;
use Craft;
use craft\base\Element;

class Issue extends Element
{
    /**
     * @var string|null Subject of the issue
     */
    public ?string $subject = null;

    /**
     * @var string|null Staus of the issue
     */
    public ?string $status = null;

    /**
     * @var int|null Owner (User) of the issue
     */
    public ?int $ownerId = null;

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
     * @return \codemonauts\its\elements\db\IssueQuery The newly created [[CategoryQuery]] instance.
     */
    public static function find(): IssueQuery
    {
        return new IssueQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%its_issues}}', [
                    'id' => $this->id,
                    'subject' => $this->subject,
                    'status' => $this->status,
                    'ownerId' => $this->ownerId,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%its_issues}}', [
                    'subject' => $this->subject,
                    'status' => $this->status,
                    'ownerId' => $this->ownerId,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }
}