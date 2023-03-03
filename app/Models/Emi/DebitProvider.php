<?php

namespace RZP\Models\Emi;

use RZP\Exception;

class DebitProvider
{
    const DEFAULT_DEBIT_EMI_PROVIDERS = 0;

    const HDFC = 'HDFC';
    const KKBK = 'KKBK';
    const INDB = 'INDB';

    protected static $providers = [
        self::HDFC,
        self::KKBK,
        self::INDB,
    ];


    protected static $providerBitPositionMap = [
        self::HDFC => 1,
        self::KKBK => 2,
        self::INDB => 3,
    ];

    public static function checkProviderValidity($provider)
    {
        if (in_array($provider, self::$providers, true) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid debit emi provider given');
        }
    }

    public static function getAllDebitEmiProviders()
    {
        return self::$providers;
    }

    public static function getEnabledDebitEmiProviders($providers): array
    {
        return self::getConsolidatedEnabledDebitEmiProviders(1, $providers);
    }

    public static function getConsolidatedEnabledDebitEmiProviders(int $debitEmi, $providers): array
    {
        $debitEmiProviders = [];

        foreach (self::$providerBitPositionMap as $provider => $value)
        {
            $index = $providers >> ($value-1);

            if (($index & 1) > 0)
            {
                $debitEmiProviders[$provider] = $debitEmi;
            }
            else
            {
                $debitEmiProviders[$provider] = 0;
            }
        }

        return $debitEmiProviders;
    }

    public static function getHexValue(array $providers): int
    {
        $debitEmiProvider = 0;

        foreach ($providers as $provider => $value)
        {
            self::checkProviderValidity(strtoupper($provider));

            $value = (int) $value;

            $bitPosition = self::$providerBitPositionMap[strtoupper($provider)];

            // Set the bit
            if ($value === 1)
            {
                $debitEmiProvider = $debitEmiProvider | (1<<($bitPosition-1));
            }
            // Reset the bit
            else
            {
                $debitEmiProvider = $debitEmiProvider & (~(1<<($bitPosition-1)));
            }
        }

        return $debitEmiProvider;
    }
}
