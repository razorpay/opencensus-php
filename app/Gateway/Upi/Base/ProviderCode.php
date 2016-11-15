<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Models\Bank\IFSC;

class ProviderCode
{
    const HDFCBANK                  = 'hdfcbank';
    const ICICI                     = 'icici';
    const YBL                       = 'ybl';
    
    protected static $bankCodes = [
        self::HDFCBANK          => IFSC::HDFC,
        self::ICICI             => IFSC::ICIC,
        self::YBL               => IFSC::YESB     
    ];
    
    public static function getBankCode($provider)
    {
        return self::$bankCodes[$provider] ?? NULL;
    }
}