<?php

namespace codemonauts\its\exceptions;

use yii\base\Exception;

class IssueNotFoundException extends Exception
{
    public function getName(): string
    {
        return 'Issue not found';
    }
}
