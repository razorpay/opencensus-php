<?php

namespace RZP\Models\Schedule;

class Steps
{
    const STEP_LIST = [
        Period::WEEKLY       => 'Day',
        Period::MONTHLY_DATE => 'Day',
        Period::MONTHLY_WEEK => 'Day',
        Period::HOURLY       => 'Hour',
        Period::DAILY        => 'Day',
    ];
}
