<?php

namespace RZP\Gateway\Upi\Icici;

use RZP\Error;
use RZP\Gateway\Base\ErrorCodes\Upi\ErrorCodeDescriptions;
class ResponseCode
{
    const CODES = array(
        1    => 'User profile not found',
        4    => 'Response parsing error',
        5    => 'The amount given is invalid',
        9    => 'Payment declined by user',
        10   => 'Insufficient data',
        99   => 'Transaction cannot be processed',
        101  => 'Unknown Server Error',
        5000 => 'The request has failed with reasons not listed below',
        5001 => 'The merchant Id is not valid',
        5002 => 'Transaction is already initiated with this merchant transaction id',
        5003 => 'Merchant transaction id is null',
        5004 => 'Invalid packet',
        5005 => 'Given collect by date is less than current date',
        5006 => 'No transaction initiated with given transaction id based on merchant id',
        5007 => 'Invalid VPA',
        // PSP is not registered
        5008 => 'Invalid VPA',
        // Service unavailable. Please try later.
        5009 => 'Payment was unsuccessful due to request timeout',
        5010 => 'Technical Error',
        5011 => 'Duplicate transaction',
        5012 => 'Duplicate transaction (offline)',
        5013 => 'Invalid VPA',
        5014 => 'Insufficient amount',
        5017 => 'Sorry you can\'t initiate refund request',
        5019 => 'Please try after sometime.',
        5020 => 'No response from Beneficiary Bank. Please wait for recon before initiating the transaction again.',
        5021 => 'Transaction Timed out. Please check transaction status before initiating again',
        5023 => 'Multiple transactions against given parameter.',
        5024 => 'Record not found against given parameters',
        5025 => 'Please enter valid refund amount',
        5026 => 'Invalid Consumer number',
        5027 => 'Invalid merchant prefix',
        5029 => 'No Response From Switch',
        5030 => 'Please try again',

        8000 => 'Invalid Encrypted Request',
        8001 => 'JSON IS EMPTY',
        8002 => 'INVALID_JSON',
        8003 => 'INVALID_FIELD_FORMAT_OR_LENGTH',
        8004 => 'MISSING_REQUIRED_FIELD_DATA',
        8005 => 'MISSING_REQUIRED_FIELD',
        8006 => 'INVALID_FIELD_LENGTH',
        8007 => 'INVALID JSON,OPEN CURLY BRACE MISSING',
        8008 => 'INVALID JSON,END CURLY BRACE MISSING',
        8009 => 'Internal Server Error',
        8010 => 'Internal Service Failure',
        9999 => 'No response from Bank',
    );

    public static function getResponseMessage($code)
    {
        // Check if the Error Code Description is in own Gateway Error Map
        // Then Type Cast it into int and return the Description
        if (isset(self::CODES[(int) $code]) === true)
        {
            return self::CODES[$code];
        }

        // Check if it's in the NPCI Error Code Description Associative Array
        // No need to Type Cast since all the NPCI Error Codes are String
        if (isset(ErrorCodeDescriptions::$errorDescriptionMap[$code]) === true)
        {
            return ErrorCodeDescriptions::$errorDescriptionMap[$code];
        }

        // If not found then return Unknown Response Code Message
        return 'Unknown Gateway Response Code';
    }
}
