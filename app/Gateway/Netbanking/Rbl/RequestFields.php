<?php

namespace RZP\Gateway\Netbanking\Rbl;


class RequestFields
{
	const BANK_ID            = "BANK_ID";
	const LOGIN_FLAG         = "AuthenticationFG.LOGIN_FLAG";
	const USER_TYPE          = "AuthenticationFG.USER_TYPE";
	const MENU_ID            = "AuthenticationFG.MENU_ID";
	const CALL_MODE          = "AuthenticationFG.CALL_MODE";
	const CATEGORY_ID        = "CATEGORY_ID";
	const RETURN_URL         = "RU";
	const QUERY_STRING       = "QS";
	const CURRENCY           = "ShoppingMallTranFG.TRAN_CRN";
	const AMOUNT             = "ShoppingMallTranFG.TXN_AMT";
	const PAYEE_ID           = "ShoppingMallTranFG.PID";
	const MERCHANT_REFERENCE = "ShoppingMallTranFG.PRN";
	const MERCHANT_NAME      = "ShoppingMallTranFG.ITC";
	const ACCOUNT_NUMBER     = "ShoppingMallTranFG.ACNT_NUM";
}
