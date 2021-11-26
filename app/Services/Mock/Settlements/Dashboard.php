<?php

namespace RZP\Services\Mock\Settlements;

use RZP\Services\Settlements\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function migrateToPayout(array $input) : array
    {
        return [
            'count'=>           1,
            'status_code'=>     200
        ];
    }
}
