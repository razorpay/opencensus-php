<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Models\Bank\IFSC;

class ProviderCode
{
    const AXISBANK                  = 'axisbank';
    const CNRB                      = 'cnrb';
    const DCB                       = 'dcb';
    const HDFCBANK                  = 'hdfcbank';
    const ICICI                     = 'icici';
    const KBL                       = 'kbl';
    const MAHB                      = 'mahb';
    const PNB                       = 'pnb';
    const POCKETS                   = 'pockets';
    const SIB                       = 'sib';
    const TJSB                      = 'tjsb';
    const UNIONBANK                 = 'unionbank';
    const UNIONBANKOFINDIA          = 'unionbankofindia';
    const UTBI                      = 'utbi';
    const VIJB                      = 'vijb';
    const YBL                       = 'ybl';

    protected static $bankCodes = [
        self::AXISBANK                  => IFSC::UTIB,
        self::CNRB                      => IFSC::CNRB,
        self::DCB                       => IFSC::DCBL,
        self::HDFCBANK                  => IFSC::HDFC,
        self::ICICI                     => IFSC::ICIC,
        self::KBL                       => IFSC::KARB,
        self::MAHB                      => IFSC::MAHB,
        self::PNB                       => IFSC::PUNB,
        self::POCKETS                   => IFSC::ICIC,
        self::SIB                       => IFSC::SIBL,
        self::TJSB                      => IFSC::TJSB,
        self::UNIONBANK                 => IFSC::UBIN,
        self::UNIONBANKOFINDIA          => IFSC::UBIN,
        self::UTBI                      => IFSC::UTBI,
        self::VIJB                      => IFSC::VIJB,
        self::YBL                       => IFSC::YESB
    ];

    public static function getBankCode($provider)
    {
        return self::$bankCodes[$provider] ?? NULL;
    }
}