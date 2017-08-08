<?php

namespace RZP\Models\Settings;

use Setting;

class Types
{
    const OPENWALLET_CLOSED             = 'openwallet.closed';
    const OPENWALLET_SEMICLOSED_LIMITED = 'openwallet.semiclosed_limited';
    const OPENWALLET_SEMICLOSED_KYC     = 'openwallet.semiclosed_kyc';

    /**
     * Pre-defined settings and their descriptions
     * @var array
     */
    protected static $defined = [
        self::OPENWALLET_CLOSED             => [
            'max_limit'       => 'Max Balance',
            'max_load_value'  => 'Daily Load Limit',
            'max_load_txns'   => 'Daily Load Transactions Limit',
            'max_spend_value' => 'Daily Spend Limit',
            'max_spend_txns'  => 'Daily Spend Transactions Limit',
        ],
        self::OPENWALLET_SEMICLOSED_LIMITED => [
            'max_limit'       => 'Max Balance',
            'max_load_value'  => 'Daily Load Limit',
            'max_load_txns'   => 'Daily Load Transactions Limit',
            'max_spend_value' => 'Daily Spend Limit',
            'max_spend_txns'  => 'Daily Spend Transactions Limit',
        ],
        self::OPENWALLET_SEMICLOSED_KYC     => [
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
    ];

    public static function getWithDescriptions(string $key = null): array
    {
        $data = static::$defined;

        if ($key !== null)
        {
            $data = static::$defined[$key] ?? [];
        }

        return array_dot($data);
    }

    protected static function dotFlatten($array, $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value)
        {
            if ((is_array($value) === true) and
                (empty($value) === false))
            {
                $results = array_merge($results, static::dotFlatten($value, $prepend . $key . '.'));
            }
            else
            {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }
}
