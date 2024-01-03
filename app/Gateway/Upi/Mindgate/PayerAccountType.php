<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Models\PaymentsUpi;

class PayerAccountType
{
    const PAYER_ACCOUNT_TYPE_SAVINGS = 'savings';

    const PAYER_ACCOUNT_TYPE_CURRENT = 'current';

    const PAYER_ACCOUNT_TYPE_CREDIT = 'credit';

    const PAYER_ACCOUNT_TYPE_PPIWALLET = 'ppiwallet';

    const PAYER_ACCOUNT_TYPE_WALLET = 'wallet';

    const PAYER_ACCOUNT_TYPE_NRE = 'NRE';

    const PAYER_ACCOUNT_TYPE_NRO = 'NRO';

    const PAYER_ACCOUNT_TYPE_CREDITLINE   = 'creditline';
    const PAYER_ACCOUNT_TYPE_CREDITLINE01 = 'creditline01';
    const PAYER_ACCOUNT_TYPE_CREDITLINE02 = 'creditline02';
    const PAYER_ACCOUNT_TYPE_CREDITLINE03 = 'creditline03';
    const PAYER_ACCOUNT_TYPE_CREDITLINE04 = 'creditline04';
    const PAYER_ACCOUNT_TYPE_CREDITLINE05 = 'creditline05';
    const PAYER_ACCOUNT_TYPE_CREDITLINE06 = 'creditline06';
    const PAYER_ACCOUNT_TYPE_CREDITLINE07 = 'creditline07';
    const PAYER_ACCOUNT_TYPE_CREDITLINE08 = 'creditline08';
    const PAYER_ACCOUNT_TYPE_CREDITLINE09 = 'creditline09';
    const PAYER_ACCOUNT_TYPE_CREDITLINE10 = 'creditline10';
    const PAYER_ACCOUNT_TYPE_CL01         = 'cl01';
    const PAYER_ACCOUNT_TYPE_CL011        = 'cl011';
    const PAYER_ACCOUNT_TYPE_CL012        = 'cl012';
    const PAYER_ACCOUNT_TYPE_CL013        = 'cl013';
    const PAYER_ACCOUNT_TYPE_CL014        = 'cl014';
    const PAYER_ACCOUNT_TYPE_CL015        = 'cl015';
    const PAYER_ACCOUNT_TYPE_CL02         = 'cl02';
    const PAYER_ACCOUNT_TYPE_CL03         = 'cl03';
    const PAYER_ACCOUNT_TYPE_CL04         = 'cl04';
    const PAYER_ACCOUNT_TYPE_CL05         = 'cl05';
    const PAYER_ACCOUNT_TYPE_CL06         = 'cl06';
    const PAYER_ACCOUNT_TYPE_CL07         = 'cl07';
    const PAYER_ACCOUNT_TYPE_CL08         = 'cl08';
    const PAYER_ACCOUNT_TYPE_CL09         = 'cl09';
    const PAYER_ACCOUNT_TYPE_CL10         = 'cl10';

    const SUPPORTED_PAYER_ACCOUNT_TYPES = [
        self::PAYER_ACCOUNT_TYPE_SAVINGS,
        self::PAYER_ACCOUNT_TYPE_CURRENT,
        self::PAYER_ACCOUNT_TYPE_CREDIT,
        self::PAYER_ACCOUNT_TYPE_PPIWALLET,
        self::PAYER_ACCOUNT_TYPE_NRE,
        self::PAYER_ACCOUNT_TYPE_NRO,
        self::PAYER_ACCOUNT_TYPE_WALLET,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE01,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE02,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE03,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE04,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE05,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE06,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE07,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE08,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE09,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE10,
        self::PAYER_ACCOUNT_TYPE_CL01,
        self::PAYER_ACCOUNT_TYPE_CL011,
        self::PAYER_ACCOUNT_TYPE_CL012,
        self::PAYER_ACCOUNT_TYPE_CL013,
        self::PAYER_ACCOUNT_TYPE_CL014,
        self::PAYER_ACCOUNT_TYPE_CL015,
        self::PAYER_ACCOUNT_TYPE_CL02,
        self::PAYER_ACCOUNT_TYPE_CL03,
        self::PAYER_ACCOUNT_TYPE_CL04,
        self::PAYER_ACCOUNT_TYPE_CL05,
        self::PAYER_ACCOUNT_TYPE_CL06,
        self::PAYER_ACCOUNT_TYPE_CL07,
        self::PAYER_ACCOUNT_TYPE_CL08,
        self::PAYER_ACCOUNT_TYPE_CL09,
        self::PAYER_ACCOUNT_TYPE_CL10,
    ];

// Every supported gateway payer account type should be mapped with internal payer account type
    protected static $payerAccountTypeMapping = [
        self::PAYER_ACCOUNT_TYPE_SAVINGS => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_CREDIT => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT,
        self::PAYER_ACCOUNT_TYPE_PPIWALLET  => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_WALLET,
        self::PAYER_ACCOUNT_TYPE_WALLET     => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_WALLET,
        self::PAYER_ACCOUNT_TYPE_CURRENT => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_NRE => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_NRO => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE01 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE02 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE03 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE04 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE05 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE06 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE07 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE08 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE09 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CREDITLINE10 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL01 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL011 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL012 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL013 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL014 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL015 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL02 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL03 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL04 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL05 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL06 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL07 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL08 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL09 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
        self::PAYER_ACCOUNT_TYPE_CL10 => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE,
    ];

    /**
     * Get internal payer account type based on gateway payer account type
     * @param $gatewayPayerAccountType
     * @return string
     */
    public static function getPayerAccountType($gatewayPayerAccountType): string
    {
        return self::$payerAccountTypeMapping[$gatewayPayerAccountType];
    }
}
