<?php

namespace RZP\Models\BankTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'payer_account'  => 'required|string|max:20',
        'payer_ifsc'     => 'required|string|size:11',
        'payee_account'  => 'required|string|max:20',
        'payee_ifsc'     => 'required|string|size:11',
        'mode'           => 'required|custom',
        'transaction_id' => 'required|string|max:30',
        'time'           => 'required|integer',
        'amount'         => 'required|integer|min:0',
        'description'    => 'sometimes|string|max:100',
    );

    protected static $validateRules = array(
        'payer_account'  => 'required|string|max:20',
        'payer_ifsc'     => 'required|string|size:11',
        'payee_account'  => 'required|string|max:20',
        'payee_ifsc'     => 'required|string|size:11',
        'mode'           => 'required|custom',
        'transaction_id' => 'required|string|max:30',
        'time'           => 'required|integer',
        'amount'         => 'required|integer|min:0',
        'description'    => 'sometimes|string|max:100',
    );

    protected static $payRules = array(
        'payer_account'  => 'required|string|max:20',
        'payer_ifsc'     => 'required|string|size:11',
        'payee_account'  => 'required|string|max:20',
        'payee_ifsc'     => 'required|string|size:11',
        'mode'           => 'required|custom',
        'transaction_id' => 'required|string|max:30',
        'time'           => 'required|integer',
        'amount'         => 'required|integer|min:0',
        'description'    => 'sometimes|string|max:100',
    );

    protected function validateMode($attribute, $mode)
    {
        if (Mode::isValid($mode) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid bank transfer Mode: ' . $mode);
        }
    }
}
