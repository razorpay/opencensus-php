<?php

namespace RZP\Models\Settlement\Ondemand;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use Razorpay\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Validator extends Base\Validator
{
    const SETTLEMENT_ONDEMAND_INPUT                = 'settlement_ondemand_input';
    const SETTLEMENT_ONDEMAND_FEES_INPUT           = 'settlement_ondemand_fees_input';
    const FETCH_BY_TIMESTAMP_INPUT                 = 'fetch_by_timestamp_input';
    const SETTLEMENT_ONDEMAND_LINKED_ACCOUNT_INPUT = 'settlement_ondemand_linked_account_input';
    const MAX_ONDEMAND_AMOUNT                      = 2000000000;
    const MIN_ONDEMAND_AMOUNT                      = 100;
    const MIN_ONDEMAND_AMOUNT_FOR_DASHBOARD        = 200000;
    const MIN_PARTIAL_ES_AMOUNT                    = 10000;

    protected static $createRules = [
        Entity::AMOUNT                         => 'required|integer|custom',
        Entity::TOTAL_AMOUNT_PENDING           => 'sometimes|integer',
        Entity::TOTAL_AMOUNT_SETTLED           => 'sometimes|integer',
        Entity::TOTAL_AMOUNT_REVERSED          => 'sometimes|integer',
        Entity::TOTAL_FEES                     => 'sometimes|integer',
        Entity::TOTAL_TAX                      => 'sometimes|integer',
        Entity::CURRENCY                       => 'sometimes|size:3',
        Entity::NARRATION                      => 'sometimes|nullable|string|max:30',
        Entity::REMARKS                        => 'sometimes|nullable|string',
        Entity::NOTES                          => 'sometimes|nullable|array',
        Entity::MAX_BALANCE                    => 'sometimes|boolean',
        Entity::SCHEDULED                      => 'sometimes|boolean',
        Entity::SETTLEMENT_ONDEMAND_TRIGGER_ID => 'sometimes|nullable|string|size:14',
        Entity::STATUS                         => 'required',
    ];

    protected static $settlementOndemandInputRules = [
        Entity::AMOUNT              => 'required_without:settle_full_balance|integer|custom',
        'settle_full_balance'       => 'required_without:amount|boolean',
        Entity::CURRENCY            => 'sometimes|in:INR',
        'description'               => 'sometimes|nullable|string|max:30',
        Entity::NOTES               => 'sometimes|nullable|array',
    ];

    public static $fetchByTimestampInputRules = [
        'from'                => 'sometimes|epoch',
        'to'                  => 'sometimes|epoch',
        'count'               => 'sometimes|integer|min:1|max:100',
        'expand'              => 'sometimes|nullable|array',
        'skip'                => 'sometimes|integer',
        'status'              => 'sometimes|string|in:created,initiated,processed,partially_processed,reversed',
    ];

    public static $settlementOndemandFeesInputRules = [
        Entity::AMOUNT              => 'required|integer|custom',
        Entity::CURRENCY            => 'sometimes|in:INR',
    ];

    protected static $settlementOndemandLinkedAccountInputRules = [
        Entity::MERCHANT_ID              => 'required|size:14',
        'settlement_ondemand_trigger_id' => 'required|size:14',
        'mode'                           => 'required|in:test,live',
        Entity::AMOUNT                   => 'required|integer'

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

        // $app = App::getFacadeRoot();
        //
        // if (($app['basicauth']->isProxyAuth() === true) &&
        //     (($value < self::MIN_ONDEMAND_AMOUNT_FOR_DASHBOARD) === true) &&
        //     ($app['basicauth']->getMerchant()->isFeatureEnabled(Feature\Constants::ES_AUTOMATIC) === false))
        // {
        //     throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_AMOUNT_LESS_THAN_MIN_LIMIT_FOR_NON_ES_AUTOMATIC_MERCHANTS,
        //     null,
        //     [
        //         'amount' => $value,
        //     ]);
        // }
        else if ($value < self::MIN_ONDEMAND_AMOUNT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AMOUNT_LESS_THAN_MIN_ONDEMAND_AMOUNT,
                null,
                [
                    'amount' => $value
                ]);
        }
    }

    public static function validateOndemandSettlementAmount($value)
    {
        if ($value > self::MAX_ONDEMAND_AMOUNT)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_AMOUNT_MAX_LIMIT_EXCEEDED,
                null,
                [
                    'amount' => $value,
                ]);
        }
        else if ($value < self::MIN_PARTIAL_ES_AMOUNT)
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
