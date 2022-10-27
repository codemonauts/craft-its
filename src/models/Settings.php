<?php

namespace codemonauts\its\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * @var array $statuses Statuses the issues can have.
     */
    public array $statuses = [];
}
