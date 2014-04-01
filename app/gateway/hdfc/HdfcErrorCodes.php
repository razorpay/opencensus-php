<?php

namespace Gateway/HdfcGateway;

class HdfcGatewayErrorHandler
{
	protected static $error = array(
		'GW00150' => 'GW00150-Missing required data',
		'GW00151' => 'GW00151-Invalid Action type',
		'GW00152' => 'GW00152-Invalid Transaction Amount',
		'GW00153' => 'GW00153-Invalid Transaction ID',
		'GW00154' => 'GW00154-Invalid Terminal ID',
		'GW00181' => 'GW00181-Failed Credit Greater Than Debit check',
		'GW00205' => 'GW00205-Invalid Subsequent Transaction',
		'GW00157' => 'GW00157-Invalid Payment Instrument',
		'GW00165' => 'GW00165-Invalid Track ID data',
		'GW00166' => 'GW00166-Invalid Card Number data',
		'GW00167' => 'GW00167-Invalid Currency Code data',
		'GW00170' => 'GW00170-Terminal ID mismatch',
		'GW00171' => 'GW00171-Payment Instrument mismatch',
		'GW00160' => 'GW00160-Invalid Brand.',
		'GW00161' => 'GW00161-Invalid Card/Member Name data',
		'GW00162' => 'GW00162-Invalid User Defined data',
		'GW00163' => 'GW00163-Invalid Address data',
		'GW00164' => 'GW00164-Invalid Zip Code data',
		'GW00183' => 'GW00183-Card Verification Digit Required',
		'GW00258' => 'GW00258-Transaction denied: Negative BIN',
		'GW00259' => 'GW00259-Transaction denied: Declined Card',
		'GV00005' => 'GV00005-Certificate chain validation failed',
		'GV00006' => 'GV00006-Certificate chain validation error',
		'GV00011' => 'GV00011-Invalid expiration date',
		'PY20006' => 'PY20006-Invalid Brand',
		'PY20001' => 'PY20001-Invalid Action Type',
		'PY20002' => 'PY20002-Invalid amount',

		// Below error code is our custom one to handle unknow error cases 
		// returned from bank.
		'RP00001' => 'RP00001-Invalid Error Code');

	protected static $errorMap = array(
		'GW00150' => Status::GATEWAY_TRANSACTION_MISSING_DATA,
		'GW00151' => Status::GATEWAY_TRANSACTION_INVALID_ACTION,
		'GW00152' => Status::GATEWAY_CARD_INVALID_AMOUNT,
		'GW00153' => Status::GATEWAY_TRANSACTION_INVALID_ID,
		'GW00154' => Status::GATEWAY_GATEWAY_INVALID_TERMNIAL_ID,
		'GW00181' => Status::GATEWAY_TRANSACTION_CREDIT_LESS_THAN_DEBIT,
		'GW00205' => STATUS::GATEWAY_GATEWAY_SUBSEQUENT_TRANSACTION,
		'GW00157' => Status::GATEWAY_NOT_UNDERSTOOD_ERROR,
		'GW00165' => Status::GATEWAY_TRANSACTION_INVALID_ID,
		'GW00166' => Status::GATEWAY_CARD_INVALID_NUMBER,
		'GW00167' => Status::GATEWAY_TRANSACTION_INVALID_CURRENCY,
		'GW00170' => Status::GATEWAY_GATEWAY_INVALID_TERMNIAL_ID,
		'GW00171' => Status::GATEWAY_NOT_UNDERSTOOD_ERROR,
		'GW00160' => Status::GATEWAY_CARD_INVALID_BRAND,
		'GW00161' => Status::GATEWAY_CARD_INVALID_NAME,
		'GW00162' => Status::GATEWAY_TRANSACTION_INVALID_UDF,
		'GW00163' => Status::GATEWAY_CARD_INVALID_ADDRESS,
		'GW00164' => Status::GATEWAY_CARD_INVALID_ZIP,
		'GW00183' => Status::GATEWAY_CARD_MISSING_CVC,
		'GW00258' => Status::GATEWAY_TRASACTION_DENIED_NEGATIVE_BIN,
		'GW00259' => Status::GATEWAY_CARD_DECLINED,
		'GV00005' => Status::GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED,
		'GV00006' => Status::GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED,
		'GV00011' => Status::GATEWAY_CARD_INVALID_EXPIRY_DATE,
		'PY20006' => Status::GATEWAY_CARD_INVALID_BRAND,
		'PY20001' => Status::GATEWAY_TRANSACTION_INVALID_ACTION,
		'PY20002' => Status::GATEWAY_CARD_INVALID_AMOUNT,
		)

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
			return $this->invalidErrorCode;
		}

		$errorCode = substr($error, 7);

		if (! array_key_exists($this->error, $errorCode))
		{
			return $this->invalidErrorCode;
		}

		return $errorCode;
	}

	public static function translateError($response)
	{
		if (isset($response['error']['code']))
		{

		}

		
	}
}