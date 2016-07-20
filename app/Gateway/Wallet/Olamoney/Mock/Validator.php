<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $authorizeRules   = array(
        'paymentId' => 'required|string',
        'bill'      => 'required|regex:"^[a-zA-Z0-9+/=]"',
        'phone'     => 'required|integer'
    );

    protected static $refundRules = array(
        'accessToken'       => 'required|string',
        'command'           => 'required|in:refund',
        'uniqueId'          => 'required|string',
        'comments'          => 'required|string',
        'udf'               => 'required|string',
        'hash'              => 'required|string',
        'returnUrl'         => 'sometimes|url',
        'notificationUrl'   => 'sometimes|url',
        'amount'            => 'required|numeric',
        'balanceType'       => 'required|string',
        'balanceName'       => 'required|string',
        'saleId'            => 'required|string',
        'currency'          => 'required|in:INR'
    );

    protected static $verifyRules = array(
        'uniqueBillId'  => 'required|string',
        'accessToken'   => 'required|string',
        'timestamp'     => 'required|date_format:Y-m-d H:i:s',
        'hash'          => 'required|string',
    );
}
