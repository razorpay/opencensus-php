<?php

namespace RZP\Services\Mock\Settlements;

use RZP\Services\Settlements\Api as BaseSettlementsApi;

class Api extends BaseSettlementsApi
{
    public function migrateMerchantConfigCreate(array $input, $mode = null) : array
    {
        return $this->getDefaultMerchantConfigArray();
    }

    public function migrateMerchantConfigUpdate(array $input, $mode = null) : array
    {
        return $this->getDefaultMerchantConfigArray();
    }

    public function migrateBankAccount($input, $via, $mode)
    {
        return [
            'id' => 'bankAccount123',
        ];
    }

    private function getDefaultMerchantConfigArray()
    {
        return [
            'config' => [
                'active' => false,
                'features' => [
                    'block' => [
                        'reason' => '',
                        'status' => false,
                    ],
                    'disable' => [
                        'reason' => '',
                        'status' => false,
                    ],
                    'hold' => [
                        'reason' => '',
                        'status' => false,
                    ],
                ],
                'preferences' => [
                    'channel' => 'ICICI',
                    'mode' => '',
                    'narration_text' => '',
                ],
                'schedules' => [
                    'adjustment' => [
                        'default' => 'instant1234567',
                    ],
                    'commission' => [
                        'default' => 'instant1234567',
                    ],
                    'credit_repayment' => [
                        'default' => 'instant1234567',
                    ],
                    'fund_account_validation' => [
                        'default' => 'instant1234567',
                    ],
                    'payment' => [
                        'domestic:default' => 'instant1234567',
                        'international:default' => 'instant1234567',
                    ],
                    'payout' => [
                        'default' => 'instant1234567',
                    ],
                    'refund' => [
                        'default' => 'instant1234567',
                    ],
                    'reversal' => [
                        'default' => 'instant1234567',
                    ],
                    'settlement.ondemand' => [
                        'default' => 'instant1234567',
                    ],
                    'settlement_transfer' => [
                        'default' => 'instant1234567',
                    ],
                    'transfer' => [
                        'default' => 'instant1234567',
                    ],
                ],
                'types' => [
                    'aggregate' => [
                        'enable' => false,
                        'settle_to' => '',
                    ],
                    'default' => [
                        'enable' => true,
                    ],
                    'transaction_level' => [
                        'enable' => false,
                    ],
                ],
            ],
        ];
    }
}
