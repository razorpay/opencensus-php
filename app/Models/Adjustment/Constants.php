<?php

namespace RZP\Models\Adjustment;

use RZP\Constants\Entity;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Constants
{
    public static $createRequestTraceCodeMap = [
        Entity::DISPUTE  => TraceCode::DISPUTE_ADJUSTMENT_CREATE_REQUEST,
        Entity::PAYOUT   => TraceCode::PAYOUT_ADJUSTMENT_CREATE_REQUEST,
        Entity::REVERSAL => TraceCode::REVERSAL_ADJUSTMENT_CREATE_REQUEST,
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
}
