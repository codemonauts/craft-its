<?php

namespace codemonauts\its\exceptions;

use yii\base\Exception;

class IssueTypeNotFoundException extends Exception
{
    public function getName(): string
    {
        return 'Issue type not found';
    }
}
