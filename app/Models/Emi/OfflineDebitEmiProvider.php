<?php

namespace RZP\Models\Emi;

class OfflineDebitEmiProvider
{
    const HDFC = 'HDFC';
    const ICIC = 'ICIC';
    const KKBK = 'KKBK';

    const OFFLINE_DEBIT = 'offline_debit';
    const OFFLINE_DISABLED_DEBIT_EMI = 'offline_disabled_debit_emi';

    protected static $offline_providers = [
        self::HDFC,
        self::ICIC,
        self::KKBK,
    ];

    public static function getEnabledOfflineProviders($all_addon_methods, $addon_methods): array
    {
        $consolidatedEnabledEmiProviders = [];

        if(isset($addon_methods[self::OFFLINE_DISABLED_DEBIT_EMI]) === true)
        {
            foreach ($all_addon_methods[self::OFFLINE_DEBIT] as $provider)
            {
                if(in_array($provider, $addon_methods[self::OFFLINE_DISABLED_DEBIT_EMI]))
                {
                    $consolidatedEnabledEmiProviders[$provider] = 0;
                }
                else
                {
                    $consolidatedEnabledEmiProviders[$provider] = 1;
                }
            }
        }
        else
        {
            foreach ($all_addon_methods[self::OFFLINE_DEBIT] as $provider)
            {
                $consolidatedEnabledEmiProviders[$provider] = 0;
            }
        }

        return $consolidatedEnabledEmiProviders;
    }

    public static function getConsolidatedEnabledOfflineProviders($offline_debit_emi_enabled, $all_addon_methods, $addon_methods): array
    {
        $consolidatedEnabledEmiProviders = [];

        $disabledProviders = $addon_methods[self::OFFLINE_DISABLED_DEBIT_EMI] ?? [];

        foreach ($all_addon_methods[self::OFFLINE_DEBIT] as $provider)
        {
            $consolidatedEnabledEmiProviders[$provider] = (in_array($provider, $disabledProviders) || !$offline_debit_emi_enabled) ? 0 : 1;
        }

        return $consolidatedEnabledEmiProviders;
    }
}
