<?php

namespace RZP\Models\Growth;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $sendPricingBundleEmailRules = [
        Constants::TYPE        => 'required|string|in:'.Constants::PAYMENT_FAILURE . ',' . Constants::PAYMENT_SUCCESS . ',' . Constants::WELCOME . ',' . Constants::PLAN_UPDATED . ',' . Constants::DEFAULT_TYPE,
        Constants::DATA        => 'sometimes|array',
        Constants::MERCHANT_ID => 'required|string|size:14',
        Constants::PACKAGE_NAME => 'sometimes|string|max:30',
        Constants::TEMPLATE_NAME => 'sometimes|string|max:200',
        Constants::EMAIL_SUBJECT => 'sometimes|string|max:500'
    ];

    protected static $addAmountCreditsRules = [
        Constants::MERCHANT_ID => 'required|string|size:14',
        Constants::CAMPAIGN_NAME => 'required|string',
        Constants::AMOUNT => 'required|integer',
        Constants::EXPIRED_AT => 'required|integer'
    ];

    protected static $createInternalTransactionRules = [
        Constants::MERCHANT_ID   => 'required|string|size:14',
        Constants::TRANSACTOR_ID => 'required|string',
        Constants::AMOUNT        => 'required|integer',
        Constants::JOURNAL_ID    => 'required|string|size:14',
        Constants::CURRENCY      => 'required|string',
        Constants::IS_REVERSAL   => 'sometimes|boolean',
    ];
}
