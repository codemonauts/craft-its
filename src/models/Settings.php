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
}
