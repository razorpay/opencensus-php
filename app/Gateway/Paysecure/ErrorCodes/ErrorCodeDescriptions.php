<?php

namespace RZP\Gateway\Paysecure\ErrorCodes;

use RZP\Gateway\Base;

class ErrorCodeDescriptions extends Base\ErrorCodes\Cards\ErrorCodeDescriptions
{
    public static $errorCodeDescriptions = [
        // Check BIN error codes
        ErrorCodes::EC_01   => 'Missing Parameter',
        ErrorCodes::EC_02   => 'Invalid Command',
        ErrorCodes::EC_400  => 'General Error',
        ErrorCodes::EC_401  => 'Command is Null or Empty',
        ErrorCodes::EC_402  => 'XML is Null or Empty',
        ErrorCodes::EC_404  => 'SQL Exception',
        ErrorCodes::EC_406  => 'Not Authenticated',
        ErrorCodes::EC_407  => 'Not Authorized',
        ErrorCodes::EC_408  => 'XML Data Error',
        ErrorCodes::EC_410  => 'Invalid BIN',
        ErrorCodes::EC_412  => 'Issuer Authentication Failure',

        // Callback error codes
        ErrorCodes::ACCU100 => 'Authentication Failed',
        ErrorCodes::ACCU200 => 'User pressed cancel button',
        ErrorCodes::ACCU400 => 'User was inactive',
        ErrorCodes::ACCU600 => 'Invalid data posted to Paysecure',
        ErrorCodes::ACCU700 => 'Card issuer error',
        ErrorCodes::ACCU800 => 'General error',
        ErrorCodes::ACCU999 => 'Modal popup was opened successfully',


        ErrorCodes::EC_110  => 'NO ACCT',
        ErrorCodes::EC_120  => 'ACCT CLOSED',
        ErrorCodes::EC_399  => 'SYSTEM UNAVAILABLE',
        ErrorCodes::EC_ED   => 'E-commerce decline',
        ErrorCodes::EC_CA   => 'Compliance error code for acquirer',
        ErrorCodes::EC_CI   => 'Compliance error code for issuer',
        ErrorCodes::EC_M6   => 'Compliance error code for LMM',
        ErrorCodes::FAILURE => 'Failure',
    ];
}