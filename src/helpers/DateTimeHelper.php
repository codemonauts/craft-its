<?php

namespace codemonauts\its\helpers;

use Craft;
use craft\helpers\DateTimeHelper as CraftDateTimeHelper;
use DateInterval;

class DateTimeHelper
{
    public static function shortHumanDuration(mixed $dateInterval): string
    {
        $dateInterval = CraftDateTimeHelper::toDateInterval($dateInterval) ?: new DateInterval('PT0S');

        if ($dateInterval->y === 0 && $dateInterval->m === 0 && $dateInterval->d === 0 && $dateInterval->h === 0 && $dateInterval->i === 0) {
            return Craft::t('app', '{num, number} {num, plural, =1{second} other{seconds}}', ['num' => $dateInterval->s]);
        } elseif ($dateInterval->y === 0 && $dateInterval->m === 0 && $dateInterval->d === 0 && $dateInterval->h === 0) {
            return Craft::t('app', '{num, number} {num, plural, =1{minute} other{minutes}}', ['num' => $dateInterval->i]);
        } elseif ($dateInterval->y === 0 && $dateInterval->m === 0 && $dateInterval->d === 0) {
            return Craft::t('app', '{num, number} {num, plural, =1{hour} other{hours}}', ['num' => $dateInterval->h]);
        } elseif ($dateInterval->y === 0 && $dateInterval->m === 0) {
            return Craft::t('app', '{num, number} {num, plural, =1{day} other{days}}', ['num' => $dateInterval->d]);
        } else {
            return Craft::t('app', '{num, number} {num, plural, =1{month} other{months}}', ['num' => $dateInterval->m]);
        }
    }
}