<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Bank\IFSC;
use RZP\Models\Bank\Name;

class CardlessEmi
{
    const POWERED_BY = 'powered_by';
    const META = 'meta';
    const FLOW = 'flow';

    const DEBIT_CARD = 'debit_card';
    const PAN = 'pan';

    const EARLYSALARY  = 'earlysalary';
    const ZESTMONEY    = 'zestmoney';
    const FLEXMONEY    = 'flexmoney';
    const WALNUT369    = 'walnut369';
    const SEZZLE       = 'sezzle';
    const AXIO         = 'Axio';

    const HDFC = 'hdfc';
    const KKBK = 'kkbk';
    const FDRL = 'fdrl';
    const IDFB = 'idfb';
    const ICIC = 'icic';
    const HCIN = 'hcin';
    const BARB = 'barb';
    const KRBE = 'krbe';
    const CSHE = 'cshe';
    const TVSC = 'tvsc';

    const HCIN_IFSC = 'HCIN';
    const KRBE_IFSC = 'KRBE';
    const CSHE_IFSC = 'CSHE';
    const TVSC_IFSC = 'TVSC';

    /** @var int[] The minimum order/transaction amount in paisa for each cardless emi provider. */
    public const MIN_AMOUNTS = [
        self::EARLYSALARY  => 300000, // Rs. 3000
        self::ZESTMONEY    => 9900,   // Rs. 99
        self::FLEXMONEY    => 300000, // Rs. 3000
        self::WALNUT369    => 90000,  // Rs. 900
        self::SEZZLE       => 20000,  // Rs. 200
        self::BARB         => 500000, // Rs. 5000
        self::HDFC         => 500000, // Rs. 5000
        self::KKBK         => 300000, // Rs. 3000
        self::ICIC         => 700000, // Rs. 7000
        self::IDFB         => 500000, // Rs. 5000
        self::FDRL         => 500000, // Rs. 5000
        self::HCIN         => 50000,  // Rs. 500
        self::KRBE         => 240000, // Rs. 2400
        self::CSHE         => 100000, // Rs. 1000
        self::TVSC         => 300000, // Rs. 3000
    ];

    public static $fullName = [
        self::EARLYSALARY  => 'EarlySalary',
        self::ZESTMONEY    => 'ZestMoney',
        self::FLEXMONEY    => 'FlexMoney',
        self::WALNUT369    => 'Walnut369',
        self::SEZZLE       => 'Sezzle',
    ];

    // Add dashboard display names for providers which and are not banks and are not present in IFSC repo
    public static $fullDisplayName = [
        self::HCIN_IFSC  => 'Home Credit',
        self::KRBE_IFSC  => 'KreditBee',
        self::CSHE_IFSC  => 'CASHe',
        self::TVSC_IFSC  => 'TVS Credit',
    ];

    public static $fullNameForSupportedBanks = [
        self::HDFC      => 'hdfc',
        self::KKBK      => 'kkbk',
        self::FDRL      => 'fdrl',
        self::IDFB      => 'idfb',
        self::ICIC      => 'icic',
        self::HCIN      => 'hcin',
        self::BARB      => 'barb',
        self::KRBE      => 'krbe',
        self::CSHE      => 'cshe',
        self::TVSC      => 'tvsc',
    ];

    public static $supportedBanks = [
        self::FLEXMONEY => [
            IFSC::BARB,
            IFSC::HDFC,
            IFSC::KKBK,
            IFSC::FDRL,
            IFSC::IDFB,
            IFSC::ICIC,
            self::HCIN_IFSC,
            self::KRBE_IFSC,
            self::CSHE_IFSC,
            self::TVSC_IFSC,
        ]
    ];

    public static $defaultDisabledBanks = [
        self::FLEXMONEY => [
            IFSC::BARB,
            IFSC::FDRL,
            IFSC::IDFB,
            IFSC::HDFC,
            IFSC::KKBK,
            IFSC::ICIC,
            self::HCIN_IFSC,
            self::KRBE_IFSC,
            self::CSHE_IFSC,
            self::TVSC_IFSC,
        ]
    ];

    public static function exists($provider)
    {
        if (self::getProviderForBank($provider) != null)
        {
            return true;
        }
        return (isset(self::$fullName[$provider]) === true);
    }

    public static function getName($provider)
    {
        if (self::getProviderForBank($provider) != null)
        {
            return self::$fullNameForSupportedBanks[$provider];
        }
        return self::$fullName[$provider];
    }

    public static function isMultilenderProvider($provider)
    {
        return array_key_exists($provider, self::$supportedBanks);
    }

    public static function isNonBankingProvider($provider)
    {
        return in_array($provider, array_keys(self::$fullDisplayName), true);
    }

    public static function getSupportedBanksForMultilenderProvider($provider)
    {
        return self::$supportedBanks[$provider];
    }

    public static function getDefaultDisabledBanksForMultilenderProvider($provider)
    {
        return self::$defaultDisabledBanks[$provider];
    }

    public static function getCardlessEmiDirectAquirers()
    {
        return array_keys(self::$fullName);
    }

    public static function getDisplayName($codes)
    {
        $names = Name::getNames($codes);

        $names = array_merge(
                 $names,
                 array_intersect_key(
                    self::$fullDisplayName,
                    array_flip($codes)));

        asort($names);

        return $names;
    }

    public static function getProviderForBank($bank)
    {
        foreach (self::$supportedBanks as $provider => $supportedBanks)
        {
            if (in_array(strtoupper($bank), $supportedBanks) === true)
            {
                return $provider;
            }
        }
        return null;
    }
}
