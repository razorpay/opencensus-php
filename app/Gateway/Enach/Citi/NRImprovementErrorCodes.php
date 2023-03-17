<?php

namespace RZP\Gateway\Enach\Citi;

use RZP\Gateway\Enach\Citi\ErrorCodes as DebitCodes;

class NRImprovementErrorCodes
{
    // fetch error from
    const GATEWAY_ERROR_CODE    = 'gateway_error_code';
    const GATEWAY_ERROR_MESSAGE = 'gateway_error_message';
    
    const BALANCE_INSUFFICIENT = 'BALANCE_INSUFFICIENT';
    const ACCOUNT_CLOSED       = 'ACCOUNT_CLOSED';
    const MANDATE_INVALID      = 'MANDATE_INVALID';
    
    public static $temporaryErrorCodes = [
        DebitCodes::DE04  => self::BALANCE_INSUFFICIENT,
        DebitCodes::DE05  => self::BALANCE_INSUFFICIENT
    ];
    
    // Included where ever implemented
    public static $permanentErrorCodes = [];
    
    public static function getNRErrorCodes(array $row)
    {
        $errorCode = $row[self::GATEWAY_ERROR_CODE] ?? '';
        
        $temporaryErrorCode = self::$temporaryErrorCodes[$errorCode] ?? null;
        
        $permanentErrorCode = self::$permanentErrorCodes[$errorCode] ?? null;
        
        $response = [];
        
        if($temporaryErrorCode !== null)
        {
            $response["temporary_error_code"] = $temporaryErrorCode;
        }
        
        if($permanentErrorCode !== null)
        {
            $response["permanent_error_code"] = $permanentErrorCode;
        }
        
        return $response;
    }
}
