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

    protected static $enabled = array(
//        IFSC::BARB,
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

    protected static $names = array(
        self::BARB_C => 'Bank of Baroda - Corporate Banking',
        self::BARB_R => 'Bank of Baroda - Retail Banking',
        self::PUNB_C => 'Punjab National Bank - Corporate Banking',
        self::PUNB_R => 'Punjab National Bank - Retail Banking',
        self::LAVB_C => 'Lakshmi Vilas Bank - Corporate Banking',
        self::LAVB_R => 'Lakshmi Vilas Bank - Retail Banking',
    );

    protected static $processing = array(
        IFSC::ORBC,
        IFSC::UTBI,
        IFSC::SRCB);

    public static function isSupportedBank($bank)
    {
        return (in_array($bank, self::$enabled));
    }

    public static function findUnsupportedBanks($banks)
    {
        return array_diff($banks, self::$enabled);
    }

    public static function getAllBanks()
    {
        return self::$enabled;
    }

    public static function getDisabledBanks($banks)
    {
        return array_diff(self::$enabled, $banks);
    }

    public static function getEnabledBanks()
    {
        return self::$enabled;
    }

    public static function getNames($codes)
    {
        $names = array_intersect_key(self::$names, array_flip($codes));

        $names = array_merge($names, Name::getNames($codes));

        return $names;
    }
}