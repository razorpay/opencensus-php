<?php

namespace RZP\Models\Adjustment;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;

class Constants
{
    public static $createRequestTraceCodeMap = [
        Entity::DISPUTE  => TraceCode::DISPUTE_ADJUSTMENT_CREATE_REQUEST,
        Entity::PAYOUT   => TraceCode::PAYOUT_ADJUSTMENT_CREATE_REQUEST,
        Entity::REVERSAL => TraceCode::REVERSAL_ADJUSTMENT_CREATE_REQUEST,
        Entity::PAYMENT  => TraceCode::ADJUSTMENT_CREATE_REQUEST,
    ];


    public static function getAdjustmentCreateRequestTraceCode(string $source)
    {
        if (empty(self::$createRequestTraceCodeMap[$source]) === true)
        {
            throw new Exception\LogicException(
                'Unsupported Source for the Adjustment Create Request',
                null,
                [
                    'source' => $source,
                ]);
        }

        return self::$createRequestTraceCodeMap[$source];
    }

    public static array $merchantMapForCustomAdjustments = [
        '10000000000000' => '100DemoAccount', // used for unit test
        '7LAuMvKMcy7s0f' => 'KgIs6yuCiDxhbB',
    ];

    public static array $merchantMapForAdjustmentsDescription = [
        '10000000000000' => 'test adjustment creation _', // used for unit test
        '7LAuMvKMcy7s0f' => 'PB NC EMI adjustment _',
    ];
}
