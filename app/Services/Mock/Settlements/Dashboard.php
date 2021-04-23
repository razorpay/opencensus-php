<?php

namespace RZP\Services\Mock\Settlements;

use RZP\Services\Settlements\Dashboard as BaseDahboard;

class Dashboard extends BaseDahboard
{
    public function migrateToPayout(array $input) : array
    {
        return [
            'count'=>           1,
            'status_code'=>     200
        ];
    }
}
