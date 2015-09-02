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
        IFSC::ABNA,
        IFSC::ALLA,
        IFSC::ANDB,
        IFSC::BBKM,
        IFSC::BKDN,
        IFSC::BKID,
        IFSC::CBIN,
        IFSC::CIUB,
        IFSC::CNRB,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::DCBL,
        IFSC::DCBL,
        IFSC::DEUT,
        IFSC::DLXB,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::JAKA,
        IFSC::JSBP,
        IFSC::KARB,
        IFSC::KVBL,
        IFSC::MAHB,
        IFSC::NKGS,
        IFSC::ORBC,
        IFSC::PMCB,
        IFSC::PSIB,
        IFSC::RATN,
        IFSC::SIBL,
        IFSC::SRCB,
        IFSC::SVCB,
        IFSC::SYNB,
        IFSC::TMBL,
        IFSC::TNSC,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UTBI,
        IFSC::UTIB,
        IFSC::VIJB,
        IFSC::VYSA,
        IFSC::YESB,
        Netbanking::BARB_C,
        Netbanking::BARB_R,
        Netbanking::PUNB_C,
        Netbanking::PUNB_R,
        Netbanking::LAVB_C,
        Netbanking::LAVB_R,
    );

    protected static $sbiepay = array(
        IFSC::SBTR,
        IFSC::CSBK,
        IFSC::JAKA,
        IFSC::MAHB,
        IFSC::DEUT,
        IFSC::VIJB,
        IFSC::PSIB,
        IFSC::SIBL,
        IFSC::BKID,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBMY,
        IFSC::STBP,
        IFSC::UTBI,
        IFSC::IDIB,
        IFSC::CIUB,
        IFSC::DLXB,
        IFSC::ICIC,
        IFSC::YESB,
        IFSC::KVBL,
        IFSC::FDRL,
        IFSC::ORBC,
        IFSC::CORP,
        IFSC::INDB,
        IFSC::HDFC,
        IFSC::BBKM,
        IFSC::KARB,
        IFSC::ANDB,
        IFSC::CNRB,
        IFSC::RATN,
        IFSC::UBIN,
        IFSC::CBIN,
        IFSC::PUNB,
        IFSC::IOBA,
        IFSC::SBIN,
        IFSC::VYSA,
        IFSC::IBKL,
        IFSC::BKDN,
        IFSC::DCBL,
        IFSC::TMBL,
        IFSC::SYNB,
        IFSC::CITI,
        IFSC::LAVB
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
        //
        // Merge paytm and billdesk supported banks and remove
        // duplicate values
        //
        return array_unique(array_merge(self::$paytm, self::$billdesk, self::$sbiepay));
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

        $names = array_merge(
                    $names,
                    array_intersect_key(
                        self::$names,
                        array_flip($codes)));

        asort($names);

        return $names;
    }

    public static function getName($code)
    {
        if (defined(__CLASS__ . '::' . $code))
        {
            return self::$names[$code];
        }

        return Name::getName($code);
    }

    public static function getPaytmSupportedBanks()
    {
        return self::$paytm;
    }

    public static function getBilldeskSupportedBanks()
    {
        return self::$billdesk;
    }

    public static function getSbiepaySupportedBanks()
    {
        return self::$sbiepay;
    }

    public static function isPaytmSupportedBank($bank)
    {
        return in_array($bank, self::$paytm);
    }

    public static function isBilldeskSupportedBank($bank)
    {
        return in_array($bank, self::$billdesk);
    }

    public static function isSbiepaySupportedBank($bank)
    {
        return in_array($bank, self::$sbiepay);
    }
}