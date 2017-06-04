<?php

namespace RZP\Models\BankTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYER_ACCOUNT  => 'required|string|max:20',
        Entity::PAYER_IFSC     => 'required|string|size:11',
        Entity::PAYEE_ACCOUNT  => 'required|string|max:20',
        Entity::PAYEE_IFSC     => 'required|string|size:11',
        Entity::MODE           => 'required|custom',
        Entity::UTR            => 'required|string|max:30',
        Entity::TIME           => 'required|integer',
        Entity::AMOUNT         => 'required|integer|min:0',
        Entity::DESCRIPTION    => 'sometimes|string|max:100',
    ];

    protected function validateMode($attribute, $mode)
    {
        if (Mode::isValid($mode) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid bank transfer Mode: ' . $mode);
        }
    }
}
