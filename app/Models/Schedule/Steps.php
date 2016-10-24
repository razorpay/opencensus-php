<?php

namespace RZP\Models\Schedule;

class Steps
{
    const ANCHORED_STEPS = [
        Period::WEEKLY       => 'Day',
        Period::MONTHLY_DATE => 'Day',
        Period::MONTHLY_WEEK => 'Day',
    ];

    const NON_ANCHORED_STEPS = [
        Period::HOURLY       => 'Hour',
        Period::DAILY        => 'Day',
    ];
}
