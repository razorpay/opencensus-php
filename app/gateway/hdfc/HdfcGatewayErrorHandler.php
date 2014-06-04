<?php

namespace Gateway\HdfcGateway;

use Exceptions\Status;

class HdfcGatewayErrorHandler
{
    protected static $error = array(
        'FSS0001'   => 'Authentication Not Available',
        'FSS00002'  => 'Duplicate Transaction Request',
        'GW00150'   => 'Missing Required Data',
        'GW00151'   => 'Invalid Action type',
        'GW00152'   => 'Invalid Transaction Amount',
        'GW00153'   => 'Invalid Transaction ID',
        'GW00154'   => 'Invalid Terminal ID',
        'GW00159'   => 'Card Number Missing',
        'GW00181'   => 'Failed Credit Greater Than Debit check',
        'GW00205'   => 'Invalid Subsequent Transaction',
        'GW00157'   => 'Invalid Payment Instrument',
        'GW00165'   => 'Invalid Track ID data',
        'GW00166'   => 'Invalid Card Number data',
        'GW00167'   => 'Invalid Currency Code data',
        'GW00170'   => 'Terminal ID Mismatch',
        'GW00171'   => 'Payment Instrument Mismatch',
        'GW00160'   => 'Invalid Brand.',
        'GW00161'   => 'Invalid Card/Member Name data',
        'GW00162'   => 'Invalid User Defined data',
        'GW00163'   => 'Invalid Address data',
        'GW00164'   => 'Invalid Zip Code data',
        'GW00177'   => 'Failed Support Greater Than Auth check',
        'GW00183'   => 'Card Verification Digit Required',
        'GW00201'   => 'Support Error Auth not found',
        'GW00258'   => 'Transaction Denied: Negative BIN',
        'GW00259'   => 'Transaction Denied: Declined Card',
        'GV00004'   => 'PARes Status Not Sucessful',
        'GV00005'   => 'Certificate Chain Validation Failed',
        'GV00006'   => 'Certificate Chain Validation Error',
        'GV00007'   => 'Signature Validation Failed',
        'GV00008'   => 'Signature Validation Failed',
        'GV00011'   => 'Invalid Expiration Date',
        'PY20006'   => 'Invalid Brand',
        'PY20001'   => 'Invalid Action Type',
        'PY20002'   => 'Invalid amount',

        // Below error code is our custom one to handle unknow error cases 
        // returned from bank.
        'RP00001'   => 'Invalid Error Code',
        'RP00002'   => 'Uncaptured Transaction'
        );

    /**
     * Maps error codes from HDFC Gateway to the 
     * app's gateway agnostic codes
     * @var array
     */
    protected static $errorMap = array(
        'FSS0001'   => Status::GATEWAY_AUTHENTICATION_NOT_AVAILABLE,
        'FSS00002'  => Status::GATEWAY_DUPLICATE_TRANSACTION_REQUEST,
        'GW00150'   => Status::GATEWAY_TRANSACTION_MISSING_DATA,
        'GW00151'   => Status::GATEWAY_TRANSACTION_INVALID_ACTION,
        'GW00152'   => Status::GATEWAY_CARD_INVALID_AMOUNT,
        'GW00153'   => Status::GATEWAY_TRANSACTION_INVALID_ID,
        'GW00154'   => Status::GATEWAY_GATEWAY_INVALID_TERMINAL_ID,
        'GW00181'   => Status::GATEWAY_TRANSACTION_CREDIT_LESS_THAN_DEBIT,
        'GW00205'   => STATUS::GATEWAY_GATEWAY_SUBSEQUENT_TRANSACTION,
        'GW00157'   => Status::GATEWAY_NOT_UNDERSTOOD_ERROR,
        'GW00165'   => Status::GATEWAY_TRANSACTION_INVALID_ID,
        'GW00166'   => Status::GATEWAY_CARD_INVALID_NUMBER,
        'GW00167'   => Status::GATEWAY_TRANSACTION_INVALID_CURRENCY,
        'GW00170'   => Status::GATEWAY_GATEWAY_INVALID_TERMINAL_ID,
        'GW00171'   => Status::GATEWAY_NOT_UNDERSTOOD_ERROR,
        'GW00160'   => Status::GATEWAY_CARD_INVALID_BRAND,
        'GW00161'   => Status::GATEWAY_CARD_INVALID_NAME,
        'GW00162'   => Status::GATEWAY_TRANSACTION_INVALID_UDF,
        'GW00163'   => Status::GATEWAY_CARD_INVALID_ADDRESS,
        'GW00164'   => Status::GATEWAY_CARD_INVALID_ZIP,
        'GW00183'   => Status::GATEWAY_CARD_MISSING_CVC,
        'GW00177'   => Status::GATEWAY_SUPPORT_FAILED,
        'GW00201'   => Status::GATEWAY_SUPPORT_AUTH_NOT_FOUND,
        'GW00258'   => Status::GATEWAY_TRASACTION_DENIED_NEGATIVE_BIN,
        'GW00259'   => Status::GATEWAY_CARD_DECLINED,
        'GV00004'   => Status::GATEWAY_PARES_NOT_SUCCESFUL,
        'GV00005'   => Status::GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED,
        'GV00006'   => Status::GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED,
        'GV00007'   => Status::GATEWAY_SIGNATURE_VALIDATION_FAILED,
        'GV00008'   => Status::GATEWAY_SIGNATURE_VALIDATION_FAILED,
        'GV00011'   => Status::GATEWAY_CARD_INVALID_EXPIRY_DATE,
        'PY20006'   => Status::GATEWAY_CARD_INVALID_BRAND,
        'PY20001'   => Status::GATEWAY_TRANSACTION_INVALID_ACTION,
        'PY20002'   => Status::GATEWAY_CARD_INVALID_AMOUNT,
        'RP00001'   => Status::GATEWAY_UNKNOWN_ERROR);

    public static $invalidErrorCode = 'RP00001';

    public static function parseErrorStr($error)
    {
        //
        // All error codes returned by hdfc gateway start with !ERROR!
        // Let's make sure it's present here
        //
        
        $str = substr($error, 0, 7);

        if ($str !== '!ERROR!')
        {
            return static::$invalidErrorCode;
        }

        $errorCode = substr($error, 7);

        if (! array_key_exists($this->error, $errorCode))
        {
            return $this->invalidErrorCode;
        }

        return $errorCode;
    }

    public static function translateError($error)
    {
        return static::translateErrorCode($error);
    }

    public static function translateErrorCode($errorCode)
    {
        if (! array_key_exists($this->error, $errorCode))
        {
            $errorCode = $this->invalidErrorCode;
        }
        
        $newErrorCode = $this->error[$errorCode];

        return $newErrorCode;   
    }

    public static function unknownError()
    {
        return static::$invalidErrorCode;
    }

    public static function translateEnrollError($response)
    {
    	return $response['error'];
    }

    public static function parseErrorInString($result)
    {
        $error_codes = array_keys(static::$error);
        $error = array();
        
        foreach($error_codes as $error_code)
        {
            if (strpos($result, $error_code) !== false)
            {
                $error['code'] = $error_code;
                $error['message'] = static::$error[$error_code];
                return $error;
            }
        }

        return false;
    }
}