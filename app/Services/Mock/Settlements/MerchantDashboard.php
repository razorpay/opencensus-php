<?php

namespace RZP\Services\Mock\Settlements;

use RZP\Services\Settlements\MerchantDashboard as BaseDashboard;

/**
 * RSR-1970 : mock class for settlements merchant dashboard
 */
class MerchantDashboard extends BaseDashboard {
    public function getSettlementForTransaction(array $input)
    {
        return [
            'settlement'  => [
                'id'         => 'settlement0001',
                'utr'        => 'RATNH22188655484',
                'settled_by' => 'Razorpay',
                'created_at' => '1657189863',
            ],
            'status_code' =>     200,
        ];
    }
}
