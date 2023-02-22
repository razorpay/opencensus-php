<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Base;
use RZP\Exception;

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

    protected static $getAttributesRules = [
        Entity::PAYMENT_ID                  => 'required|string',
    ];

    protected static $createDashboardRules = [
        Entity::PAYMENT_ID                  => 'required|string',
        Entity::TYPE                        => 'required|string|in:' . Constants::FRAUD_TYPES_CSV,
        Entity::SUB_TYPE                    => 'sometimes|custom',
        Entity::REPORTED_TO_ISSUER_AT       => 'required|int',
        Entity::REPORTED_TO_RAZORPAY_AT     => 'required|int',
        Constants::HAS_CHARGEBACK           => 'required|string|in:0,1',
        Entity::IS_ACCOUNT_CLOSED           => 'required|string|in:0,1',
        Entity::AMOUNT                      => 'required|numeric|min:0',
        Entity::CURRENCY                    => 'required|string|in:INR,USD',
        Entity::REPORTED_BY                 => 'required|string|in:' . Constants::REPORTED_BY_CSV,
        Constants::SKIP_MERCHANT_EMAIL      => 'required|string|in:0,1',
    ];

    const ALLOWED_FRAUD_TYPES_FOR_CYBERCRIME_FRAUD_PAYMENT_ENTITY_CREATION = [
        BankCodes::FRAUD_CODE_3,
        BankCodes::FRAUD_CODE_6,
    ];

    public function validTypeForCyberCrimeFraudPaymentEntityCreation($type)
    {
        if(in_array($type, self::ALLOWED_FRAUD_TYPES_FOR_CYBERCRIME_FRAUD_PAYMENT_ENTITY_CREATION))
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException('invalid fraud type');
    }
}
