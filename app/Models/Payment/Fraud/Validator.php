<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Base;

class Validator extends Base\Validator
{
    const CREATE_RULES = [
        Entity::PAYMENT_ID              => 'required|string|size:14',
        Entity::REPORTED_BY             => 'required|string',
        Entity::ARN                     => 'sometimes',
        Entity::TYPE                    => 'sometimes',
        Entity::SUB_TYPE                => 'sometimes',
        Entity::AMOUNT                  => 'required|int',
        Entity::CURRENCY                => 'required|string',
        Entity::BASE_AMOUNT             => 'required|int',
        Entity::REPORTED_TO_RAZORPAY_AT => 'sometimes',
        Entity::REPORTED_TO_ISSUER_AT   => 'sometimes',
        Entity::CHARGEBACK_CODE         => 'sometimes',
        Entity::IS_ACCOUNT_CLOSED       => 'sometimes',
        Entity::SOURCE                  => 'sometimes',
        Entity::BATCH_ID                => 'sometimes',
    ];

    protected static $createRules = self::CREATE_RULES;

    protected static $createOrUpdateEntityRules = self::CREATE_RULES;
}
