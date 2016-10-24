<?php

namespace RZP\Models\Schedule;

class Steps
{
    const ANCHORED_STEP = 'Day';

    const NON_ANCHORED_STEPS = [
        Period::HOURLY       => 'Hour',
        Period::DAILY        => 'Day',
        Period::WEEKLY       => 'Week',
        Period::MONTHLY_DATE => 'Month',
        Period::MONTHLY_WEEK => 'Week',
    ];
}
