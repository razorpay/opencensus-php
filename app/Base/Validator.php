<?php

namespace RZP\Base;

use RZP\Error\ErrorCode;
use RZP\Exception;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    protected static $reportRules = [
        'year'  =>  'required|digits:4',
        'month' =>  'required|digits_between:1,2',
        'day'   =>  'sometimes|digits_between:1,2',
    ];

    protected function throwExtraFieldsException($extraFields)
    {
        throw new Exception\ExtraFieldsException($extraFields);
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestValidationFailureException($messages);
    }
}
