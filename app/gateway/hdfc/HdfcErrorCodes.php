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

	public static function translateError()
	{
		
	}
}