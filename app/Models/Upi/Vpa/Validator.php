<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ADDRESS         => 'required|string|between:3,100',
        Entity::FREQUENCY       => 'sometimes|string',
        Entity::BANK_ACCOUNT_ID => 'sometimes|string',
        Entity::CUSTOMER_ID     => 'sometimes|string',
    );

    protected static $editRules = array(
        Entity::BANK_ACCOUNT_ID => 'sometimes|string',
    );

    protected static $createValidators = array(
        Entity::ADDRESS,
    );

    protected function validateAddress($input)
    {
        if (strpos($input['address'], Entity::AROBASE) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Address: '. $input['address']);
        }

        list($left, $right) = explode('@', $input['address']);

        if (strlen($left) < 3)
        {
            throw new Exception\BadRequestValidationFailureException(
            'Handle must be three characters: '. $input['address']);
        }

        if ($right !== 'razor')
        {
            throw new Exception\BadRequestValidationFailureException(
            'PSP not supported: ' . $right);
        }
    }
}
