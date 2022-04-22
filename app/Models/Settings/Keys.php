<?php

namespace RZP\Models\Settings;

use RZP\Exception\BadRequestValidationFailureException;

class Keys
{
    const OPENWALLET                     = 'openwallet';
    const OPENWALLET_CLOSED              = 'closed';
    const OPENWALLET_SEMI_CLOSED_LIMITED = 'semi_closed_limited';
    const OPENWALLET_SEMI_CLOSED_KYC     = 'semi_closed_kyc';
    const ACCOUNT_NUMBER_LENGTH          = 'account_number_length';


    /**
     * Pre-defined settings and their descriptions
     *
     * @var array
     */
    protected static $defined = [
        self::OPENWALLET => [
            self::OPENWALLET_CLOSED              => [
                'max_limit'       => 'Max Balance',
                'max_load_value'  => 'Daily Load Limit',
                'max_load_txns'   => 'Daily Load Transactions Limit',
                'max_spend_value' => 'Daily Spend Limit',
                'max_spend_txns'  => 'Daily Spend Transactions Limit',
            ],
            self::OPENWALLET_SEMI_CLOSED_LIMITED => [
                'max_limit'       => 'Max Balance',
                'max_load_value'  => 'Daily Load Limit',
                'max_load_txns'   => 'Daily Load Transactions Limit',
                'max_spend_value' => 'Daily Spend Limit',
                'max_spend_txns'  => 'Daily Spend Transactions Limit',
            ],
            self::OPENWALLET_SEMI_CLOSED_KYC     => [
                'max_limit'       => 'Max Balance',
                'max_load_value'  => 'Daily Load Limit',
                'max_load_txns'   => 'Daily Load Transactions Limit',
                'max_spend_value' => 'Daily Spend Limit',
                'max_spend_txns'  => 'Daily Spend Transactions Limit',
                'max_p2p_value'   => 'P2P Transfer Max Daily Limit',
                'max_p2p_txns'    => 'P2P Transfer Max Daily Transactions',
                'max_bank_value'  => 'Bank Transfer Max Daily Limit',
                'max_bank_txns'   => 'Bank Transfer Max Daily Transactions',
            ]
        ],
    ];

    /**
     * Return a list of pre-defined setting types
     * (and their descriptions)
     *
     * @param string $module
     *
     * @return array
     *
     * @throws BadRequestValidationFailureException
     */
    public static function getWithDescriptions(string $module): array
    {
        if (isset(static::$defined[$module]) === false)
        {
            throw new BadRequestValidationFailureException(
                'No settings are defined for the module',
                null,
                ['module' => $module]);
        }

        $data = static::$defined[$module];

        return array_dot($data);
    }
}
