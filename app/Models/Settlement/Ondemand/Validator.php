<?php

namespace RZP\Models\Settlement\Ondemand;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use Razorpay\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Validator extends Base\Validator
{
    const SETTLEMENT_ONDEMAND_INPUT      = 'settlement_ondemand_input';
    const SETTLEMENT_ONDEMAND_FEES_INPUT = 'settlement_ondemand_fees_input';
    const MAX_ONDEMAND_AMOUNT            = 2000000000;
    const MIN_ONDEMAND_AMOUNT            = 100;

    protected static $createRules = [
        Entity::AMOUNT                => 'required|integer|custom',
        Entity::TOTAL_AMOUNT_PENDING  => 'sometimes|integer',
        Entity::TOTAL_AMOUNT_REVERSED => 'sometimes|integer',
        Entity::TOTAL_FEES            => 'sometimes|integer',
        Entity::TOTAL_TAX             => 'sometimes|integer',
        Entity::CURRENCY              => 'sometimes|size:3',
        Entity::NARRATION             => 'sometimes|nullable|string',
        Entity::REMARKS               => 'sometimes|nullable|string',
        Entity::NOTES                 => 'sometimes|nullable|json',
        Entity::MAX_BALANCE           => 'sometimes|boolean',
        Entity::STATUS                => 'required',
    ];

    protected static $settlementOndemandInputRules = [
        Entity::AMOUNT              => 'required_without:max_balance|integer|custom',
        Entity::MAX_BALANCE         => 'required_without:amount|boolean',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::NARRATION           => 'sometimes|nullable|string',
        Entity::NOTES               => 'sometimes|nullable|json',
        'expand'                    => 'sometimes|boolean',
    ];

    public static $settlementOndemandFeesInputRules = [
        Entity::AMOUNT              => 'required|integer|custom',
        Entity::CURRENCY            => 'sometimes|in:INR',
    ];

    protected function validateAmount($attribute, $value)
    {
        if ($value > self::MAX_ONDEMAND_AMOUNT)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_AMOUNT_MAX_LIMIT_EXCEEDED,
            null,
            [
                'amount' => $value,
            ]);
        }

        if ($value < self::MIN_ONDEMAND_AMOUNT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AMOUNT_LESS_THAN_MIN_ONDEMAND_AMOUNT,
                null,
                [
                    'amount' => $value
                ]);
        }
    }
}
