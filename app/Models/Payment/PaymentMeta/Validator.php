<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Currency\Currency;

class Validator extends Base\Validator
{
    const FETCH_LAST = 'fetch_last';

    protected static $createRules = [
        Entity::GATEWAY_AMOUNT             => 'sometimes|integer|min:0',
        Entity::GATEWAY_CURRENCY           => 'sometimes|string|size:3|custom',
        Entity::FOREX_RATE                 => 'sometimes|numeric',
        Entity::DCC_OFFERED                => 'sometimes|boolean',
        Entity::DCC_MARK_UP_PERCENT        => 'sometimes|numeric',
        Entity::PAYMENT_ID                 => 'required|string',
        Entity::ACTION                     => 'sometimes|string',
        Entity::REFERENCE_ID               => 'sometimes|string',
        Entity::MISMATCH_AMOUNT            => 'sometimes|integer',
        Entity::MISMATCH_AMOUNT_REASON     => 'required_with:' . Entity::MISMATCH_AMOUNT . '|string|in:credit_surplus,credit_deficit',
        Entity::MCC_APPLIED                => 'sometimes|boolean',
        Entity::MCC_MARK_DOWN_PERCENT      => 'sometimes|numeric',
        Entity::MCC_FOREX_RATE             => 'sometimes|numeric',
    ];

    protected static $referenceIdRules = [
        Entity::ACTION               => 'sometimes|string',
        self::FETCH_LAST             => 'sometimes|boolean',
        Entity::REFERENCE_ID         => 'sometimes|string',
        Entity::PAYMENT_ID           => 'sometimes|string',
    ];

    protected function validateGatewayCurrency($input)
    {
        if (isset($input[Entity::GATEWAY_CURRENCY]) === false)
        {
            return;
        }

        if (Currency::isSupportedCurrency($input[Entity::GATEWAY_CURRENCY]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid DCC Currency: '.$input[Entity::GATEWAY_CURRENCY]);
        }
    }

}
