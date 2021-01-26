<?php

namespace RZP\Gateway\Ebs;

use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Processor\Netbanking;

class BankCodes
{
    // Commenting Banks which need approval for activating
    public static $bankCodeMap = [
        IFSC::ANDB => '1216',  // migrated to UBIN
        IFSC::MAHB => '1229',
        IFSC::CNRB => '1224',
        IFSC::CSBK => '1272',
        IFSC::CORP => '1135',
        IFSC::DLXB => '1273',
        IFSC::FDRL => '1029',
        IFSC::IDIB => '1143',
        IFSC::IOBA => '1213',
//      IFSC::VYSA => '1210',
        IFSC::KARB => '1133',
        IFSC::VIJB => '1379',
        IFSC::YESB => '1146',
        Netbanking::LAVB_R => '1433',
        IFSC::CBIN => '1147',
        IFSC::INDB => '1431',
        IFSC::JAKA => '1015',
        IFSC::KKBK => '1148',
        IFSC::ORBC => '1154',
        IFSC::PSIB => '1421',
        IFSC::SRCB => '1380',
        IFSC::UCBA => '1383',
        IFSC::UBIN => '1216',
        IFSC::UTBI => '1381',
        Netbanking::PUNB_R => '1381',

        IFSC::UTIB => '1004',
        IFSC::BKID => '1214',
        IFSC::CIUB => '1215',
        IFSC::ICIC => '1016',
        /*
        IFSC::SBBJ => '1033',
        IFSC::SBHY => '1034',
        IFSC::SBIN => '1032',
        IFSC::SBMY => '1038',
        IFSC::STBP => '1035',
        IFSC::SBTR => '1039',
        IFSC::HDFC => '1007',
        */
    ];

    public static $redircetDisabledBanks  = [
        IFSC::UTIB,
        IFSC::BKID,
        IFSC::CIUB,
        IFSC::ICIC,
    ];

    public static $bank302Redirect = [
        IFSC::CBIN,
        IFSC::INDB,
        IFSC::JAKA,
        IFSC::KKBK,
        IFSC::ORBC,
        IFSC::PSIB,
        IFSC::SRCB,
        IFSC::UCBA,
        IFSC::UBIN,
        IFSC::UTBI,
        Netbanking::PUNB_R,
    ];

    public static function getMappedCode($bankCode)
    {
        if (isset(self::$bankCodeMap[$bankCode]) === true)
        {
            return self::$bankCodeMap[$bankCode];
        }

        throw new Exception\InvalidArgumentException(
            'Bank not supported');
    }
}
