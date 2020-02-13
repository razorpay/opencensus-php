<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Currency\Currency;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY_AMOUNT             => 'sometimes|integer|min:0',
        Entity::GATEWAY_CURRENCY           => 'sometimes|string|size:3|custom',
        Entity::FOREX_RATE                 => 'sometimes|string|max:50',
        Entity::DCC_OFFERED                => 'sometimes|boolean'
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
