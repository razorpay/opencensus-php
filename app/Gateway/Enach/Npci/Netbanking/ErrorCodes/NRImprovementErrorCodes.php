<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\ErrorCodes;

use RZP\Gateway\Enach\Npci\Netbanking\ErrorCodes\FileBasedErrorCodes as DebitCodes;

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
        DebitCodes::DE4   => self::BALANCE_INSUFFICIENT
    ];

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
