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
	const GATEWAY_TRASACTION_DENIED_NEGATIVE_BIN
	const GATEWAY_TRANSACTION_INVALID_CURRENCY
	const GATEWAY_TRANSACTION_INVALID_UDF
	const GATEWAY_TRANSACTION_CREDIT_LESS_THAN_DEBIT
	
	const GATEWAY_GATEWAY_INVALID_TERMNIAL_ID = 1004;
	const GATEWAY_GATEWAY_CERTIFICATE_VALIDATION_FAILED
	const GATEWAY_GATEWAY_SUBSEQUENT_TRANSACTION

	const GATEWAY_CARD_MISSING_CVC
	const GATEWAY_CARD_INVALID_NUMBER
	const GATEWAY_CARD_INVALID_EXPIRY_DATE
	const GATEWAY_CARD_INVALID_BRAND
	const GATEWAY_CARD_INVALID_AMOUNT
	const GATEWAY_CARD_INVALID_NAME
	const GATEWAY_CARD_INVALID_ADDRESS
	const GATEWAY_CARD_INVALID_ZIP
	const GATEWAY_CARD_DECLINED
	
	const GATEWAY_NOT_UNDERSTOOD_ERROR
	const GATEWAY_UNKNOWN_ERROR

	/**
	 * Card errors catchable in the app
	 */
	const APP_CARD_INVALID_NAME
	const APP_CARD_INVALID_EXPIRY_MONTH
	const APP_CARD_INVALID_EXPIRY_YEAR
	const APP_CARD_INVALID_CVC
	const APP_CARD_INVALID_BRAND
	const APP_CARD_INVALID_CURRENCY
	const APP_CARD_INVALID_AMOUNT
	const APP_CARD_INVALID_UDF
	const APP_CARD_INVALID_NUMBER
	const APP_CARD_EXPIRED

	/**
	 * 
	 */
	const RZP_TXN_INVALID_CURRENCY


	/**
	 * Server errors
	 */
	const SERVER_DB_ERROR;
	const 

	public static gatewayUncatchableErrors = array(
		const GATEWAY_CARD_INVALID_AMOUNT,
		const GATEWAY_CARD_INVALID_ADDRESS,
		const GATEWAY_CARD_INVALID_ZIP_CODE,
		const GATEWAY_CARD_DECLINED,
		const GATEWAY_TXN_DENIED_NEGATIVE_BIN);

	public static isGatewayError($error)
	{
		return true;
	}

	public static isCardError($error)
	{
		return true;
	}
);



}