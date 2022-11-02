<?php

namespace codemonauts\its\events;

use codemonauts\its\elements\Issue;
use craft\elements\User;
use craft\events\CancelableEvent;

class HistoryEvent extends CancelableEvent
{
    public string $event;

    public Issue $issue;

    public ?User $user;

    public ?string $initiatorName;

    public ?string $additionalData;
}
