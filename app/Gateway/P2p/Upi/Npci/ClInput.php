<?php

namespace RZP\Gateway\P2p\Upi\Npci;

use RZP\Models\P2p\Device\Entity;

class ClInput
{
    const CL_VERSION            = 'cl_version';
    const CL_TOKEN              = 'cl_token';
    const CL_EXPIRY             = 'cl_expiry';

    const TXN_ID                = 'txnId';
    const DEVICE_ID             = 'deviceId';
    const APP_ID                = 'appId';
    const MOBILE_NUMBER         = 'mobileNumber';

    // Entities
    const BANK_ACCOUNT          = 'bank_account';
    const TRANSACTION           = 'transaction';
    const PAYER                 = 'payer';
    const PAYEE                 = 'payee';
    const UPI                   = 'upi';

    // Other fields which are passed as input for multiple actions

    public static $allowed = [
        self::CL_TOKEN      => 'string|max:1000',
        self::CL_EXPIRY     => 'epoch',
        self::TXN_ID        => 'string|max:40',
        self::DEVICE_ID     => 'string|max:100',
        self::APP_ID        => 'string|max:100',
        self::MOBILE_NUMBER => 'string|max:10',
        self::BANK_ACCOUNT  => 'array',
        self::TRANSACTION   => 'array',
        self::PAYER         => 'array',
        self::PAYEE         => 'array',
        self::UPI           => 'array',
    ];
}
