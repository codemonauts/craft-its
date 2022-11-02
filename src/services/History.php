<?php

namespace codemonauts\its\services;

use codemonauts\its\elements\Issue;
use codemonauts\its\events\HistoryEvent;
use codemonauts\its\exceptions\IssueNotFoundException;
use craft\db\Query;
use craft\elements\User;
use yii\base\Component;
use yii\base\Event;

class History extends Component
{
    public const EVENT_HISTORY_ISSUE_CREATED = 'issueCreated';

    public const EVENT_HISTORY_ISSUE_UPDATED = 'issueUpdated';

    public const EVENT_HISTORY_ISSUE_CLOSED = 'issueClosed';

    public const EVENT_HISTORY_ISSUE_REOPEN = 'issueReopen';

    public const EVENT_HISTORY_ISSUE_COMMENT = 'issueComment';

    public function addEvent(string $event, int|Issue $issue, ?User $user = null, ?string $initiatorName = null, ?string $data = null): bool
    {
        if (is_int($issue)) {
            $issue = Issue::findOne($issue);
            if (!$issue) {
                throw new IssueNotFoundException();
            }
        }

        // Give plugins a chance to modify them
        $triggeredEvent = new HistoryEvent([
            'event' => $event,
            'issue' => $issue,
            'user' => $user,
            'initiatorName' => $initiatorName,
            'additionalData' => $data,
        ]);
        Event::trigger(static::class, $event, $triggeredEvent);

        if ($triggeredEvent->isValid) {
            $eventRecord = new \codemonauts\its\records\History();
            $eventRecord->event = $event;
            $eventRecord->issueId = $issue->id;
            $eventRecord->initiatorId = $user->id;
            $eventRecord->initiatorName = $triggeredEvent->initiatorName;
            $eventRecord->data = $triggeredEvent->additionalData;

            return $eventRecord->insert();
        }

        return true;
    }

    public function getHistoryOfIssue(int $issueId, int $limit = 10)
    {
        return (new Query())
            ->select([
                'id',
                'event',
                'initiatorName',
                'initiatorId',
                'data',
                'dateCreated',
                'uid',
            ])
            ->from(['{{%its_history}}'])
            ->all();
    }
}