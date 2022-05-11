<?php

namespace RZP\Models\Emi;

use RZP\Exception;

class DebitProvider
{
    const DEFAULT_DEBIT_EMI_PROVIDERS = 0;

    const HDFC = 'HDFC';

    protected static $providers = [
        self::HDFC,
    ];


    protected static $providerBitPositionMap = [
        self::HDFC => 1,
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

    public static function getEnabledDebitEmiProviders(int $debitEmi, $providers): array
    {
        $debitEmiProviders = [];

        foreach (self::$providerBitPositionMap as $provider => $value)
        {
            if (($providers & $value) > 0)
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
            $value = (int) $value;

            $bitPosition = self::$providerBitPositionMap[strtoupper($provider)];

            // Set the bit
            if ($value === 1)
            {
                $debitEmiProvider = $debitEmiProvider | $bitPosition;
            }
            // Reset the bit
            else
            {
                $debitEmiProvider = $debitEmiProvider & (~$bitPosition);
            }
        }

        return $debitEmiProvider;
    }
}
