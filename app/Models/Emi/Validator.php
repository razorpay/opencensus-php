<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::BANK                    => 'required_without:network|size:4',
        Entity::NETWORK                 => 'required_without:bank|size:5|in:AMEX',
        Entity::DURATION                => 'required|integer|in:3,6,9,12,18,24',
        Entity::RATE                    => 'required|integer',
        Entity::METHODS                 => 'sometimes|in:card,wallet,netbanking',
        Entity::MIN_AMOUNT              => 'sometimes|integer'
    );

    protected static $createValidators = array(
        'bank',
    );

    protected function validateBank($input)
    {
        if (in_array($input['bank'], Gateway::$emiBanks) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid bank name: '. $input['bank']);
        }
    }
}
