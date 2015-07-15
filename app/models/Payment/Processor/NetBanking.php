<?php

namespace Models\Payment\Processor;

use Models\Bank\IFSC;
use Models\Bank\Name;

class Netbanking
{
    const BARB_C = 'BARB_C';
    const BARB_R = 'BARB_R';
    const PUNB_C = 'PUNB_C';
    const PUNB_R = 'PUNB_R';
    const LAVB_C = 'PUNB_C';
    const LAVB_R = 'PUNB_R';

    protected static $names = array(
        self::BARB_C => 'Bank of Baroda - Corporate Banking',
        self::BARB_R => 'Bank of Baroda - Retail Banking',
        self::PUNB_C => 'Punjab National Bank - Corporate Banking',
        self::PUNB_R => 'Punjab National Bank - Retail Banking',
        self::LAVB_C => 'Lakshmi Vilas Bank - Corporate Banking',
        self::LAVB_R => 'Lakshmi Vilas Bank - Retail Banking',
    );

    protected static $paytm = array(
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CSBK,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::ICIC,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::JAKA,
        IFSC::KKBK,
        IFSC::MAHB,
        IFSC::PUNB,
        IFSC::UBIN,
        IFSC::UTIB,
        IFSC::VIJB,
        IFSC::VYSA,
        IFSC::YESB,
    );

    protected static $billdesk = array(
        IFSC::HDFC,
        IFSC::ALLA,
        IFSC::BKID,
        IFSC::CIUB,
        IFSC::UTIB,
        IFSC::ICIC,
        IFSC::ANDB,
        IFSC::BBKM,
        IFSC::MAHB,
        IFSC::CBIN,
        IFSC::CNRB,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::DEUT,
        IFSC::DCBL,
        IFSC::BKDN,
        IFSC::DLXB,
        IFSC::FDRL,
        IFSC::IBKL,
        IFSC::INDB,
        IFSC::IDIB,
        IFSC::VYSA,
        IFSC::IOBA,
        IFSC::JAKA,
        IFSC::KARB,
        IFSC::KVBL,
        IFSC::ORBC,
        IFSC::PMCB,
        IFSC::PSIB,
        IFSC::ABNA,
        IFSC::RATN,
        IFSC::SIBL,
        IFSC::SVCB,
        IFSC::SRCB,
        IFSC::SYNB,
        IFSC::TMBL,
        IFSC::TNSC,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UTBI,
        IFSC::VIJB,
        IFSC::YESB,
        IFSC::DCBL,
        IFSC::JSBP,
        IFSC::NKGS,
        Netbanking::BARB_C,
        Netbanking::BARB_R,
        Netbanking::PUNB_C,
        Netbanking::PUNB_R,
        Netbanking::LAVB_C,
        Netbanking::LAVB_R,
    );

    public static function isSupportedBank($bank)
    {
        return (in_array($bank, self::getAllBanks()));
    }

    public static function findUnsupportedBanks($banks)
    {
        return array_diff($banks, self::getAllBanks());
    }

    public static function getAllBanks()
    {
        return array_merge(self::$paytm, self::$billdesk);
    }

    public static function getDisabledBanks($banks)
    {
        return array_diff(self::getAllBanks(), $banks);
    }

    public static function getEnabledBanks()
    {
        return self::getAllBanks();
    }

    public static function getNames($codes)
    {
        $names = Name::getNames($codes);

        $names = array_merge($names, array_intersect_key(self::$names, array_flip($codes)));

        asort($names);

        return $names;
    }

    public static function getPaytmSupportedBanks()
    {
        return self::$paytm;
    }

    public static function getBilldeskSupportedBanks()
    {
        return self::$billdesk;
    }
}