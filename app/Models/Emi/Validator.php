<?php

namespace RZP\Models\Emi;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::BANK                    => 'required_without:network|size:4',
        Entity::NETWORK                 => 'required_without:bank|max:5|in:AMEX',
        Entity::DURATION                => 'required|integer|in:3,6,9,12,18,24',
        Entity::RATE                    => 'required|integer|min:0',
        Entity::METHODS                 => 'sometimes|in:card,wallet,netbanking',
        Entity::MIN_AMOUNT              => 'sometimes|integer|min:100',
        Entity::ISSUER_PLAN_ID          => 'sometimes',
        Entity::SUBVENTION              => 'sometimes|in:customer,merchant',
        Entity::MERCHANT_PAYBACK        => 'required|integer',
    );

    protected static $createValidators = array(
        Entity::BANK,
    );

    protected function validateBank($input)
    {
        if (isset($input[Entity::BANK]) === false)
        {
            return;
        }

        if (isset($input[Entity::NETWORK]) === true)
        {
            throw new Exception\BadRequestValidationFailureException('Either of bank or network must be sent in input');
        }

        if (in_array($input[Entity::BANK], Gateway::$emiBanks, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('invalid bank name: '. $input[Entity::BANK]);
        }
    }
}
