<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Validator extends \Razorpay\Spine\Validator
{
    protected function throwExtraFieldsException($extraFields)
    {
        throw new Exception\ExtraFieldsException($extraFields);
    }
}