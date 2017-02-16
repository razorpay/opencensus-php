<?php

namespace RZP\Base;

use RZP\Exception;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    protected function throwExtraFieldsException($extraFields)
    {
        throw new Exception\ExtraFieldsException($extraFields);
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestValidationFailureException($messages);
    }

    public static function validateInputKeyExists(array $input, $key)
    {
        if (isset($input[$key]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $key . ' not given in the input');
        }
    }
}
