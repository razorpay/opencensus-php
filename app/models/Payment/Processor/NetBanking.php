<?php

namespace Models\Payment\Processor;

use Models\Bank\IFSC;

class NetBanking
{
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
}