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
        Entity::RATE                    => 'required|integer',
        Entity::METHODS                 => 'sometimes|in:card,wallet,netbanking',
        Entity::MIN_AMOUNT              => 'sometimes|integer',
        Entity::ISSUER_PLAN_ID          => 'sometimes'
    );

    protected static $createValidators = array(
        'bank',
    );

    protected function validateBank($input)
    {
        if ((isset($input['bank']) === true) and
            (in_array($input['bank'], Gateway::$emiBanks, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid bank name: '. $input['bank']);
        }
    }
}
