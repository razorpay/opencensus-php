<?php

namespace RZP\Gateway\Netbanking\Rbl;


class RequestFields
{
    const FORM_ID            = 'FORMSGROUP_ID__';
    const TRANSACTION_FLAG   = '__START_TRAN_FLAG__';
    const FG_BUTTON          = 'FG_BUTTONS__';
    const ACTION_LOAD        = 'ACTION.LOAD';
    const BANK_ID            = 'BANK_ID';
    const LOGIN_FLAG         = 'AuthenticationFG.LOGIN_FLAG';
    const USER_TYPE          = 'AuthenticationFG.USER_TYPE';
    const MENU_ID            = 'AuthenticationFG.MENU_ID';
    const CALL_MODE          = 'AuthenticationFG.CALL_MODE';
    const CATEGORY_ID        = 'CATEGORY_ID';
    const RETURN_URL         = 'RU';
    const QUERY_STRING       = 'QS';
    const CURRENCY           = 'ShoppingMallTranFG.TRAN_CRN';
    const AMOUNT             = 'ShoppingMallTranFG.TXN_AMT';
    const PAYEE_ID           = 'ShoppingMallTranFG.PID';
    const MERCHANT_REFERENCE = 'ShoppingMallTranFG.PRN';
    const MERCHANT_NAME      = 'ShoppingMallTranFG.ITC';
    const ACCOUNT_NUMBER     = 'ShoppingMallTranFG.ACNT_NUM';

    //Verify Request  fields
    const LANGUAGE_ID        = 'LANGUAGE_ID';
    const CHANNEL_ID         = 'CHANNEL_ID';
    const V_LOGIN_FLAG       = 'LOGIN_FLAG';
    const SERVICE_ID         = '__SRVCID__';
    const STATE_MODE         = 'STATEMODE';
    const RESPONSE_FORMAT    = 'OPFMT';
    const REQUEST_FORMAT     = 'IPFMT';
    const MULTIPLE_RECORDS   = 'ISMULTIREC';
    const USER_PRINCIPAL     = 'USER_PRINCIPAL';
    const CORP_PRINCIPAL     = 'CORP_PRINCIPAL';
    const ACCESS_CODE        = 'ACCESS_CODE';
    const V_PAYEE_ID         = 'BNF_ID';
    const BANK_REFERENCE     = 'REFERENCE_ID';
    const ENTITY_TYPE        = 'DESTINATION_ENTITY_TYPE';
    const TRANS_CURRENCY     = 'TRANSACTION_CURRENCY';

}
