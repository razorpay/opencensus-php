<?php

namespace RZP\Models\Ecollect;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    protected static $validateRules = array(
        'payer_account'  => 'required|string|max:20',
        'payer_ifsc'     => 'required|string|size:11',
        'payee_account'  => 'required|string|max:20',
        'payee_ifsc'     => 'required|string|size:11',
        'mode'           => 'required|in:rtgs,neft,imps,ift',
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
        'mode'           => 'required|in:rtgs,neft,imps,ift',
        'transaction_id' => 'required|string|max:30',
        'time'           => 'required|integer',
        'amount'         => 'required|integer|min:0',
        'description'    => 'sometimes|string|max:100',
    );
}
