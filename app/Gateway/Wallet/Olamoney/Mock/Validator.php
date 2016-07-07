<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $authorizeRules   = array(
        'paymentId'             => 'required|string',
        'bill'                  => 'required|regex:"^[a-zA-Z0-9+/=]"',
        'phone'                 => 'required|integer'
    );

    protected static $refundRules = array(
        'accessToken'       => 'required|string',
        'command'           => 'required|in:refund',
        'uniqueId'          => 'required|string',
        'comments'          => 'required|string',
        'udf'               => 'required|string',
        'hash'              => 'required|string',
        'returnUrl'         => 'sometimes|string',
        'notificationUrl'   => 'sometimes|string',
        'amount'            => 'required|integer',
        'balanceType'       => 'required|string',
        'balanceName'       => 'required|string',
        'saleId'            => 'required|string',
        'currency'          => 'required|in:INR'
    );
}
