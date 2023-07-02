<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Models\PaymentsUpi;

class PayerAccountType
{
    const PAYER_ACCOUNT_TYPE_SAVINGS = 'savings';

    const PAYER_ACCOUNT_TYPE_CURRENT = 'current';

    const PAYER_ACCOUNT_TYPE_CREDIT = 'credit';

    const PAYER_ACCOUNT_TYPE_PPIWALLET = 'ppiwallet';

    const PAYER_ACCOUNT_TYPE_NRE = 'NRE';

    const PAYER_ACCOUNT_TYPE_NRO = 'NRO';

    const SUPPORTED_PAYER_ACCOUNT_TYPES = [
        self::PAYER_ACCOUNT_TYPE_SAVINGS,
        self::PAYER_ACCOUNT_TYPE_CURRENT,
        self::PAYER_ACCOUNT_TYPE_CREDIT,
        self::PAYER_ACCOUNT_TYPE_PPIWALLET,
        self::PAYER_ACCOUNT_TYPE_NRE,
        self::PAYER_ACCOUNT_TYPE_NRO
    ];

// Every supported gateway payer account type should be mapped with internal payer account type
    protected static $payerAccountTypeMapping = [
        self::PAYER_ACCOUNT_TYPE_SAVINGS => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_CREDIT => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT,
        self::PAYER_ACCOUNT_TYPE_PPIWALLET => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_PPIWALLET,
        self::PAYER_ACCOUNT_TYPE_CURRENT => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_NRE => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
        self::PAYER_ACCOUNT_TYPE_NRO => PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT,
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
