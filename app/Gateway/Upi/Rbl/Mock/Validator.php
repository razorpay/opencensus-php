<?php

namespace RZP\Gateway\Upi\Rbl\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $sessionTokenRules = [
        'username' => 'required|string',
        'password' => 'required|string',
        'bcagent'  => 'required|string',
    ];

    protected static $generateAuthTokenRules = [
        'id'                    => 'required|string',
        'note'                  => 'required|string',
        'refId'                 => 'required|string',
        'hmac'                  => 'required|string',
        'refUrl'                => 'required|string',
        'header'                => 'required|array',
        'header.sessiontoken'   => 'required|string',
        'header.bcagent'        => 'required|string',
        'mobile'                => 'required|string',
        'geocode'               => 'required|string',
        'type'                  => 'required|string',
        'ip'                    => 'required|string',
        'location'              => 'required|string',
        'os'                    => 'required|string',
        'app'                   => 'required|string',
        'capability'            => 'required|string',
        'mrchOrgId'             => 'required|string',
        'aggrOrgId'             => 'required|string'
    ];

    protected static $getTransactionIdRules = [
        'id'                    => 'required|string',
        'hmac'                  => 'required|string',
        'header'                => 'required|array',
        'header.sessiontoken'   => 'required|string',
        'header.bcagent'        => 'required|string',
        'mobile'                => 'required|string',
        'geocode'               => 'required|string',
        'type'                  => 'sometimes',
        'ip'                    => 'required|string',
        'location'              => 'required|string',
        'os'                    => 'required|string',
        'app'                   => 'required|string',
        'capability'            => 'required|string',
        'mrchOrgId'             => 'required|string',
        'aggrOrgId'             => 'required|string'
    ];

    protected static $authorizeRules = [
        'id'                    => 'required|string',
        'note'                  => 'required|string',
        'refId'                 => 'required|string',
        'hmac'                  => 'required|string',
        'refUrl'                => 'required|string',
        'header'                => 'required|array',
        'header.sessiontoken'   => 'required|string',
        'header.bcagent'        => 'required|string',
        'mobile'                => 'required|string',
        'geocode'               => 'required|string',
        'type'                  => 'sometimes',
        'ip'                    => 'required|string',
        'location'              => 'required|string',
        'os'                    => 'required|string',
        'app'                   => 'required|string',
        'capability'            => 'required|string',
        'mrchOrgId'             => 'required|string',
        'aggrOrgId'             => 'required|string',
        'validupto'             => 'required|string',
        'orgTxnId'              => 'required|string',
        'txnId'                 => 'required|string',
        'payeraddress'          => 'required|string',
        'payername'             => 'required|string',
        'payeename'             => 'required|string',
        'amount'                => 'required|string',
        'payeeaddress'          => 'required|string',
    ];

    protected static $verifyRules = [
        'id'                    => 'required|string',
        'hmac'                  => 'required|string',
        'header'                => 'required|array',
        'header.sessiontoken'   => 'required|string',
        'header.bcagent'        => 'required|string',
        'mobile'                => 'required|string',
        'geocode'               => 'required|string',
        'type'                  => 'sometimes',
        'ip'                    => 'required|string',
        'location'              => 'required|string',
        'os'                    => 'required|string',
        'app'                   => 'required|string',
        'capability'            => 'required|string',
        'mrchOrgId'             => 'required|string',
        'aggrOrgId'             => 'required|string',
        'orgTxnIdorrefId'       => 'required|string',
        'flag'                  => 'required',
    ];
}
