<?php

namespace codemonauts\its\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * @var bool $myIssuesAsSource Whether to show the 'My Issues' source
     */
    public bool $myIssuesAsSource = true;

    /**
     * @var bool $allIssuesAsSource Whether to show the 'All Issues' source
     */
    public bool $allIssuesAsSource = true;

    /**
     * @var bool $useShortHumanDuration Whether to use the short human duration for the tables.
     */
    public bool $useShortHumanDuration = false;

    /**
     * @var bool $showTakeButtonWhenAssigned Whether to show the take button to quickly take an issue even if the issue is already assigned.
     */
    public bool $showTakeButtonWhenAssigned = false;
}
