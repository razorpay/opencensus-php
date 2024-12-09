<?php

namespace RZP\Models\Emi;

class OfflineCreditEmiProvider
{
    const AMEX = 'AMEX';
    const AUBL = 'AUBL';
    const BARB = 'BARB';
    const CITI = 'CITI';
    const CNRB = 'CNRB';
    const FDRL = 'FDRL';
    const HDFC = 'HDFC';
    const HSBC = 'HSBC';
    const ICIC = 'ICIC';
    const IDFB = 'IDFB';
    const INDB = 'INDB';
    const JAKA = 'JAKA';
    const KKBK = 'KKBK';
    const PUNB = 'PUNB';
    const RATN = 'RATN';
    const SBIN = 'SBIN';
    const SCBL = 'SCBL';
    const UTIB = 'UTIB';
    const YESB = 'YESB';
    const ONECARD = 'ONECARD';

    const OFFLINE_CREDIT = 'offline_credit';
    const OFFLINE_DISABLED_CREDIT_EMI = 'offline_disabled_credit_emi';

    protected static $offline_providers = [
        self::AMEX,
        self::AUBL,
        self::BARB,
        self::CITI,
        self::CNRB,
        self::FDRL,
        self::HDFC,
        self::HSBC,
        self::ICIC,
        self::IDFB,
        self::INDB,
        self::JAKA,
        self::KKBK,
        self::PUNB,
        self::RATN,
        self::SBIN,
        self::SCBL,
        self::UTIB,
        self::YESB,
        self::ONECARD,
    ];

    public static function getEnabledOfflineProviders($all_addon_methods, $addon_methods): array
    {
        $consolidatedEnabledEmiProviders = [];

        if (isset($addon_methods[self::OFFLINE_DISABLED_CREDIT_EMI]) === true)
        {
            foreach ($all_addon_methods[self::OFFLINE_CREDIT] as $provider)
            {
                if (in_array($provider, $addon_methods[self::OFFLINE_DISABLED_CREDIT_EMI]))
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
            foreach ($all_addon_methods[self::OFFLINE_CREDIT] as $provider)
            {
                $consolidatedEnabledEmiProviders[$provider] = 0;
            }
        }

        return $consolidatedEnabledEmiProviders;
    }

    public static function getConsolidatedEnabledOfflineProviders($offline_credit_emi_enabled, $all_addon_methods, $addon_methods): array
    {
        $consolidatedEnabledEmiProviders = [];

        $disabledProviders = $addon_methods[self::OFFLINE_DISABLED_CREDIT_EMI] ?? [];

        foreach ($all_addon_methods[self::OFFLINE_CREDIT] as $provider)
        {
            $consolidatedEnabledEmiProviders[$provider] = (in_array($provider, $disabledProviders) || !$offline_credit_emi_enabled) ? 0 : 1;
        }

        return $consolidatedEnabledEmiProviders;
    }
}
