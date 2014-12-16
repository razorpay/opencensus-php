<?php

namespace Models\Payment\Processor;

use Models\Bank\IFSC;

class NetBanking
{
    protected static $enabled = array(
        IFSC::UTIB,
        IFSC::BKID,
        IFSC::MAHB,
        IFSC::CNRB,
        IFSC::CSBK,
        IFSC::CBIN,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CORP,
        IFSC::DCBL,
        IFSC::BKDN,
        IFSC::DEUT,
        IFSC::DLXB,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::ICIC,
        IFSC::IBKL,
        IFSC::IDIB,
        IFSC::IOBA,
        IFSC::INDB,
        IFSC::JAKA,
        IFSC::KVBL,
        IFSC::KKBK,
        IFSC::LAVB,
        IFSC::ORBC,
        IFSC::SIBL,
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::SBTR,
        IFSC::KARB,
        IFSC::UCBA,
        IFSC::UBIN,
        IFSC::YESB);

    protected static $processing = array(
        IFSC::ORBC,
        IFSC::UTBI,
        IFSC::SRCB);

    public static function isSupportedBank($bank)
    {
        return (in_array($bank, self::$enabled));
    }
}