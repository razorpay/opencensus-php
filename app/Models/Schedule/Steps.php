<?php

namespace RZP\Models\Schedule;

class Steps
{
    const STEP_LIST = [
        Period::WEEKLY          => 'Day',
        Period::MONTHLY         => 'Day',
        Period::MONTHLY_DATE    => 'Day',
        Period::MONTHLY_WEEK    => 'Day',
        Period::HOURLY          => 'Hour',
        Period::DAILY           => 'Day',
        Period::YEARLY          => 'Day',
        Period::MINUTE          => 'Minute',
    ];

    public static function getStep(string $period): string
    {
        $stepType = self::STEP_LIST[$period];

        $step = 'add' . $stepType;

        return $step;
    }
}
