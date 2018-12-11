<?php

namespace RZP\Gateway\Paysecure;

class Fields
{
    // Request Fields
    const TOKEN                         = 'Token';
    const VERSION                       = 'Version';
    const CALLER_ID                     = 'CallerID';
    const USER_CREDENTIALS              = 'UserCredentials';
    const USER_ID                       = 'UserID';
    const USER_PASSWORD                 = 'Password';
    const PARTNER_ID                    = 'partner_id';
    const MERCHANT_PASSWORD             = 'merchant_password';

    // Check BIN2
    const CARD_BIN                      = 'card_bin';

    // Initiate
    const CARD_NO                           = 'card_no';
    const CARD_EXP_DATE                     = 'card_exp_date';
    const LANGUAGE_CODE                     = 'language_code';
    const AUTH_AMOUNT                       = 'auth_amount';
    const CURRENCY_CODE                     = 'currency_code';
    const CVD2                              = 'cvd2';
    const TRANSACTION_TYPE_INDICATOR        = 'transaction_type_indicator';
    const TID                               = 'tid';
    const STAN                              = 'stan';
    const TRAN_TIME                         = 'tran_time';
    const TRAN_DATE                         = 'tran_date';
    const MCC                               = 'mcc';
    const ACQUIRER_INSTITUTION_COUNTRY_CODE = 'acquirer_institution_country_code';
    const RETRIEVAL_REF_NUMBER              = 'retrieval_ref_number';
    const CARD_ACCEPTOR_ID                  = 'card_acceptor_id';
    const TERMINAL_OWNER_NAME               = 'terminal_owner_name';
    const TERMINAL_CITY                     = 'terminal_city';
    const TERMINAL_STATE_CODE               = 'terminal_state_code';
    const TERMINAL_COUNTRY_CODE             = 'terminal_country_code';
    const MERCHANT_POSTAL_CODE              = 'merchant_postal_code';
    const MERCHANT_TELEPHONE                = 'merchant_telephone';
    const ORDER_ID                          = 'order_id';
    const CUSTOM1                           = 'custom1';
    const CUSTOM2                           = 'custom2';
    const CUSTOM3                           = 'custom3';
    const CUSTOM4                           = 'custom4';
    const CUSTOM5                           = 'custom5';
    const BROWSER_USERAGENT                 = 'BrowserUserAgent';
    const IP_ADDRESS                        = 'IPAddress';
    const HTTP_ACCEPT                       = 'HTTPAccept';

    // Redirect Request
    const ACCU_CARDHOLDER_ID = 'AccuCardholderId';
    const ACCU_GUID          = 'AccuGuid';
    const ACCU_RETURN_URL    = 'AccuReturnURL';
    const SESSION            = 'session';
    const ACCU_REQUEST_ID    = 'AccuRequestId';

    // Response Fields
    const STATUS                = 'status';
    const ERROR_CODE            = 'errorcode';
    const ERROR_MESSAGE         = 'errormsg';

    // CheckBIN2 response
    const QUALIFIED_INTERNETPIN = 'qualified_internetpin';
    const IMPLEMENTS_REDIRECT   = 'Implements_Redirect';

    // Initiate response
    const TRAN_ID                     = 'tran_id';
    const REDIRECT_URL                = 'RedirectURL';
    const AUTHENTICATION_NOT_REQUIRED = 'AuthenticationNotRequired';
    const ACCU_HKEY                   = 'AccuHkey';
    const GUID                        = 'guid';
    const MODULUS                     = 'modulus';
    const EXPONENT                    = 'exponent';

    // Callback attributes
    const ACCU_RESPONSE_CODE = 'AccuResponseCode';

    // Authorize response
    const APPRCODE = 'apprcode';

    // Transaction status response
    const HISTORY       = 'history';
    const TRANSACTION   = 'transaction';
    const RECURRING     = 'recurring';
    const DATETIME      = 'datetime';
    const AMOUNT        = 'amount';
}
