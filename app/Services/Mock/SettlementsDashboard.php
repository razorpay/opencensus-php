<?php

namespace RZP\Services\Mock;

use RZP\Services\Settlements\Base;

class SettlementsDashboard extends Base
{
    public function migrateToPayout(array $input) : array
    {
        return [
            'count'=>           1,
            'status_code'=>     200
        ];
    }
}
