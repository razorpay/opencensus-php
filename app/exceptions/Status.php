<?php

namespace Exceptions;

class Status
{
	const SUCCESS = 0;

	/**
	 * HDFC gateway specific error codes
	 */
	
	/**
	 * General gateway specific error codes
	 * Isn't specific to hdfc gateway and 
	 * should be generally valid for gateways that
	 * are added in future
	 */
	const GATEWAY_TRANSACTION_MISSING_DATA = 1000;
	const GATEWAY_TRANSACTION_INVALID_ACTION = 1001;
	const GATEWAY_TRANSACTION_INVALID_ID = 1003;
	const GATEWAY_TRASACTION_DENIED_NEGATIVE_BIN = 1004;
	const GATEWAY_TRANSACTION_INVALID_CURRENCY = 1005;
	const GATEWAY_TRANSACTION_INVALID_UDF = 1006;
	const GATEWAY_TRANSACTION_CREDIT_LESS_THAN_DEBIT = 1110;
	
	const GATEWAY_GATEWAY_INVALID_TERMNIAL_ID = 1004;
	const GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED = 1005;
	const GATEWAY_GATEWAY_SUBSEQUENT_TRANSACTION = 1006;

	const GATEWAY_CARD_MISSING_CVC = 1007;
	const GATEWAY_CARD_INVALID_NUMBER = 1008;
	const GATEWAY_CARD_INVALID_EXPIRY_DATE = 1009;
	const GATEWAY_CARD_INVALID_BRAND = 1010;
	const GATEWAY_CARD_INVALID_AMOUNT = 1011;
	const GATEWAY_CARD_INVALID_NAME = 1012;
	const GATEWAY_CARD_INVALID_ADDRESS = 1013;
	const GATEWAY_CARD_INVALID_ZIP = 1014;
	const GATEWAY_CARD_DECLINED = 1015;
	
	const GATEWAY_NOT_UNDERSTOOD_ERROR = 1016;
	const GATEWAY_UNKNOWN_ERROR = 1017;

	/**
	 * Card errors catchable in the app
	 */
	const APP_CARD_INVALID_NAME = 1018;
	const APP_CARD_INVALID_EXPIRY_MONTH = 1019;
	const APP_CARD_INVALID_EXPIRY_YEAR = 1020;
	const APP_CARD_INVALID_CVC = 1021;
	const APP_CARD_INVALID_BRAND = 1022;
	const APP_CARD_INVALID_CURRENCY =1023;
	const APP_CARD_INVALID_AMOUNT = 1024;
	const APP_CARD_INVALID_UDF = 1025;
	const APP_CARD_INVALID_NUMBER = 1026;
	const APP_CARD_EXPIRED = 1027;

	/**
	 * 
	 */
	const RZP_TXN_INVALID_CURRENCY = 1028;


	/**
	 * Server errors
	 */
	const SERVER_DB_ERROR = 2001;

	public static $gatewayUncatchableErrors = array(
		self::GATEWAY_CARD_INVALID_AMOUNT,
		self::GATEWAY_CARD_INVALID_ADDRESS,
		self::GATEWAY_CARD_INVALID_ZIP_CODE,
		self::GATEWAY_CARD_DECLINED,
		self::GATEWAY_TXN_DENIED_NEGATIVE_BIN);

	public static function isGatewayError($error)
	{
		return true;
	}

	public static function isCardError($error)
	{
		return true;
	}
}