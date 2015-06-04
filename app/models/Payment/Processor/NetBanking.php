<?php

namespace Models\Payment\Processor;

use Models\Bank\IFSC;

class NetBanking
{
    protected static $enabled = array(
        IFSC::BARB,
        IFSC::BKID,
        IFSC::CBIN,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CNRB,
        IFSC::CORP,
        IFSC::CSBK,
        IFSC::DCBL,
        IFSC::DLXB,
        IFSC::DEUT,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::JAKA,
        IFSC::KARB,
        IFSC::KKBK,
        IFSC::KVBL,
        IFSC::LAVB,
        IFSC::MAHB,
        IFSC::PUNB,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::SBTR,
        IFSC::SIBL,
        IFSC::STBP,
        IFSC::UBIN,
        IFSC::UCBA,
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
}