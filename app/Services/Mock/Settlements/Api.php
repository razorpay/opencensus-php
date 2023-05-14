<?php

namespace RZP\Services\Mock\Settlements;

use RZP\Error\ErrorCode;
use RZP\Services\Settlements\Api as BaseSettlementsApi;

class Api extends BaseSettlementsApi
{
    private   $mockStatus;

    public function __construct($app, string $mockStatus = 'success')
    {
        parent::__construct($app);

        $this->mockStatus = $mockStatus;
    }

    public function migrateMerchantConfigCreate(array $input, $mode = null) : array
    {
        return $this->getDefaultMerchantConfigArray();
    }

    public function migrateMerchantConfigUpdate(array $input, $mode = null) : array
    {
        return $this->getDefaultMerchantConfigArray();
    }

    public function merchantConfigGet(array $input, $mode = null): array
    {
        $merchantConfig = $this->getDefaultMerchantConfigArray();

        if ($this->mockStatus === 'failure')
        {
            throw new \Exception(ErrorCode::BAD_REQUEST_ERROR);
        }

        else if ($this->mockStatus === 'settle_to_enabled')
        {
            $merchantConfig['config']['types']['aggregate']['enable'] = true;
            $merchantConfig['config']['types']['aggregate']['settle_to'] = '10000000000000';
        }
        else if ($this->mockStatus === 'aggregate_settlement_parent')
        {
            $merchantConfig['config']['preferences']['aggregate_settlement_parent'] = true;
        }
        else if ($this->mockStatus === 'aggregate_settlement_parent_false')
        {
            $merchantConfig['config']['preferences']['aggregate_settlement_parent'] = false;
        }

        return $merchantConfig;
    }

    public function migrateBankAccount($input, $mode, $via = 'payout', $merchant = null)
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

    public function ledgerCronActiveMtuCheck(array $input, $mode = null) : array
    {
        return [
            'present'               => true,
            'active_discrepancy'    => true,
            'baseline_discrepancy'  => 10,
        ];
    }

    public function ledgeCronActiveMtuAdd(array $input, $mode = null) : array
    {
        return ['id' => 'testMTU1234567'];
    }

    public function ledgeCronResultAdd(array $input, $mode = null) : array
    {
        return ['id' => 'testCronResult'];
    }

    public function ledgeCronExecutionAdd(array $input, $mode = null) : array
    {
        return ['id' => 'testCronExec12'];
    }

    public function ledgeCronExecutionUpdate(array $input, $mode = null) : array
    {
        return [];
    }

    public function ledgerReconActiveMtuUpdate(array $input, $mode = null) : array
    {
        return [];
    }

    public function ledgerReconCronTrigger(array $input, $mode = null) : array
    {
        return ['status' => 'TRIGGERED'];
    }
}
