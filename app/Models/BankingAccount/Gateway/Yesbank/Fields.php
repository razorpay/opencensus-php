<?php

namespace RZP\Models\BankingAccount\Gateway\Yesbank;

class Fields
{
    //Request Fields
    const AES_KEY               = 'aesKey';
    const CUSTOMER_ID           = 'customerId';
    const APP_ID                = 'appId';
    const AUTH_PASSWORD         = 'auth_password';
    const AUTH_USERNAME         = 'auth_username';
    const CLIENT_ID             = 'client_id';
    const CLIENT_SECRET         = 'client_secret';
    const ATTEMPT               = 'attempt';
    const GATEWAY_REF_NUMBER    = 'gateway_ref_no';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';

    //Response Fields
    const ACCOUNT_BALANCE_AMOUNT = 'accountBalanceAmount';
    const ACCOUNT_CURRENCY_CODE  = 'accountCurrencyCode';
    const FAULT_VALUE            = 'faultValue';
    const LOW_BALANCE_ALERT      = 'lowBalanceAlert';
    const DATA                   = 'data';
}
