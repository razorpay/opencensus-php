<?php

namespace RZP\Models\Terminal;

class Type
{
    // Terminal to be used for non recurring payments
    const NON_RECURRING                      = 'non_recurring';

    // Terminal to be used for first recurring transaction
    const RECURRING_3DS                      = 'recurring_3ds';

    // Terminal to be used for non-3DS transactions after first successful payment
    const RECURRING_NON_3DS                  = 'recurring_non_3ds';

    // Terminal to be used for IVR transactions
    const IVR                                = 'ivr';

    // Terminal to be used to create second recurring payments without 2fa
    const NO_2FA                             = 'no_2fa';

    // Terminal to be used for UPI pay
    const PAY                                = 'pay';

    // Terminal to be used for ATM PIN transactions
    const PIN                                = 'pin';

    // Terminals For Bharat Qr payments
    const BHARAT_QR                          = 'bharat_qr';

    // Terminals to be used for Debit recurring
    const DEBIT_RECURRING                    = 'debit_recurring';

    // Terminals to be used for Moto payments
    const MOTO                               = 'moto';

    // Terminal to be used for Collect UPI payments
    const COLLECT                            = 'collect';

    // Terminal to be used for generating Numeric Bank Account
    const NUMERIC_ACCOUNT                    = 'numeric_account';

    // Terminal to be used for generating Alpha Numeric Bank Account
    const ALPHA_NUMERIC_ACCOUNT              = 'alpha_numeric_account';

    // Terminal to be used for direct settlements without refunds
    const DIRECT_SETTLEMENT_WITHOUT_REFUND   = 'direct_settlement_without_refund';

    const POS                                = 'pos';

    // Terminal to be used for direct settlements with refunds
    const DIRECT_SETTLEMENT_WITH_REFUND      = 'direct_settlement_with_refund';

    // Terminal to be used for specific product line - business banking during bank transfer payments
    const BUSINESS_BANKING                   = 'business_banking';

    // Terminals For Upi Transfer payments
    const UPI_TRANSFER                       = 'upi_transfer';

    // Terminals for Upi One time mandates, Pay
    const OTM_PAY                            = 'otm_pay';

    // Terminals for Upi One time mandates, Collect
    const OTM_COLLECT                        = 'otm_collect';

      // Terminals for tokenisation
    const TOKENISATION                        = 'tokenisation';

    // Terminals for CardMandate
    const MANDATE_HUB                        = 'mandate_hub';

    // Terminals for Optimizer
    const OPTIMIZER                        = 'optimizer';

    // Terminals For Optimiser With Refunds Disabled
    const DISABLE_OPTIMISER_REFUNDS        = 'disable_optimizer_refunds';

    // Terminals For Optimiser with wallet auto debit
    const ENABLE_AUTO_DEBIT                = 'enable_auto_debit';

    const ONLINE                           = 'online';

    const OFFLINE                          = 'offline';

    const IN_APP                           = 'in_app';

    const SODEXO                           = "sodexo";

    const IOS                              = "ios";

    const ANDROID                          = "android";

    protected static $types = [
        self::NON_RECURRING,
        self::RECURRING_3DS,
        self::RECURRING_NON_3DS,
        self::IVR,
        self::NO_2FA,
        self::PAY,
        self::PIN,
        self::BHARAT_QR,
        self::DEBIT_RECURRING,
        self::MOTO,
        self::COLLECT,
        self::DIRECT_SETTLEMENT_WITH_REFUND,
        self::DIRECT_SETTLEMENT_WITHOUT_REFUND,
        self::NUMERIC_ACCOUNT,
        self::ALPHA_NUMERIC_ACCOUNT,
        self::BUSINESS_BANKING,
        self::UPI_TRANSFER,
        self::OTM_PAY,
        self::OTM_COLLECT,
        self::TOKENISATION,
        self::MANDATE_HUB,
        self::OPTIMIZER,
        self::DISABLE_OPTIMISER_REFUNDS,
        self::POS,
        self::ONLINE,
        self::OFFLINE,
        self::IN_APP,
        self::SODEXO,
        self::ENABLE_AUTO_DEBIT,
        self::IOS,
        self::ANDROID
    ];

    protected static $bitPosition = [
        self::NON_RECURRING                    => 1,
        self::RECURRING_3DS                    => 2,
        self::RECURRING_NON_3DS                => 3,
        self::IVR                              => 4,
        self::NO_2FA                           => 5,
        self::PAY                              => 6,
        self::PIN                              => 7,
        self::BHARAT_QR                        => 8,
        self::DEBIT_RECURRING                  => 9,
        self::MOTO                             => 10,
        self::COLLECT                          => 11,
        self::DIRECT_SETTLEMENT_WITH_REFUND    => 12,
        self::NUMERIC_ACCOUNT                  => 13,
        self::ALPHA_NUMERIC_ACCOUNT            => 14,
        self::BUSINESS_BANKING                 => 15,
        self::DIRECT_SETTLEMENT_WITHOUT_REFUND => 16,
        self::UPI_TRANSFER                     => 17,
        self::OTM_PAY                          => 18,
        self::OTM_COLLECT                      => 19,
        self::TOKENISATION                     => 20,
        self::MANDATE_HUB                      => 21,
        self::OPTIMIZER                        => 22,
        self::POS                              => 23,
        self::ONLINE                           => 24,
        self::OFFLINE                          => 25,
        self::IN_APP                           => 26,
        self::DISABLE_OPTIMISER_REFUNDS        => 27,
        self::SODEXO                           => 28,
        self::ENABLE_AUTO_DEBIT                => 29,
        self::IOS                              => 30,
        self::ANDROID                          => 31,
    ];

    /**
     * Checks if a particular type of terminal is applicable,
     * by seeing if the corresponding bit position is set.
     * Shift right 'pos' times and check LSB
     *
     * @param  string  $hexType Hex value of the bit-wise field
     * @param  string  $type    Name of the type to be checked
     * @return boolean          Whether type is applicable
     */
    public static function isApplicable($hexType, $type)
    {
        $pos = self::$bitPosition[$type];

        return ((($hexType >> ($pos - 1)) & 1) === 1);
    }

    public static function isApplicableType($types, $type)
    {
        if ((isset($types[$type]) === true) and
            ($types[$type] === '1'))
        {
            return true;
        }

        return false;
    }

    public static function getValidTypes()
    {
        return self::$types;
    }

    public static function getBitPosition($type)
    {
        return self::$bitPosition[$type];
    }

    public static function getEnabledTypes($hex)
    {
        $types = [];

        foreach (self::$types as $type)
        {
            $pos = self::$bitPosition[$type];
            $value = ($hex >> ($pos - 1)) & 1;

            if ($value)
            {
                array_push($types, $type);
            }
        }

        return $types;
    }

    /**
     * Takes the hex value and merges it
     * with the hex value of the events passed.
     *
     * @param  array $types
     * @param  int   $hex
     * @return int
     */
    public static function getHexValue($types, $hex)
    {
        foreach ($types as $type => $value)
        {
            $pos = Type::getBitPosition($type);

            $value = ($value === '1') ? 1 : 0;

            // Sets the bit value for the current type.
            $hex ^= ((-1 * $value) ^ $hex) & (1 << ($pos - 1));
        }

        return $hex;
    }

    public static function getBitPositions()
    {
        return self::$bitPosition;
    }
}
