<?php

namespace RZP\Models\Schedule;

class Anchor
{
    const CHECKS = [
        Period::WEEKLY       => 'dayOfWeek',
        Period::MONTHLY_DATE => 'day',
        Period::MONTHLY_WEEK => 'weekOfMonth',
    ];
}
