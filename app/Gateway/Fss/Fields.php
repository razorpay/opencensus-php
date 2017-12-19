<?php

namespace RZP\Gateway\Fss;

class Fields
{
    const CARD                          = 'card';

    const CVV                           = 'cvv2';

    const CURRENCY_CODE                 = 'currencycode';

    const EXPIRY_YEAR                   = 'expyear';

    const EXPIRY_MONTH                  = 'expmonth';

    // Transaction type Debit/Credit
    const TYPE                          = 'type';

    // Card holders name.
    const MEMBER                        = 'member';

    const AMOUNT                        = 'amt';

    // Purchase, Credit, etc,
    const ACTION                        = 'action';

    const TRACK_ID                      = 'trackid';

    const ERROR_URL                     = 'errorURL';

    const RESPONSE_URL                  = 'responseURL';

    const ID                            = 'id';

    const PASSWORD                      = 'password';

    const REQUEST                       = 'request';

    const UDF5                          = 'udf5';

    const TRAN_DATA                     = 'trandata';

    const TRANPORTAL_ID                 = 'tranportalId';

    const GATEWAY_PAYMENT_ID            = 'paymentid';

    const RESULT                        = 'result';

    const PAY_ID                        = 'payid';

    const AUTH_RES_CODE                 = 'authrescode';

    const TRANSACTION_ID                = 'transid';

    const LANGUAGE_ID                   = 'langid';

    // Response Fields
    const ERROR_TEXT                    = 'error_text';

    const GATEWAY_ERROR_TEXT            = 'ErrorText';

    const ACTIONVPAS                    = 'actionVPAS';

    const TRAN_ID                       = 'tranid';

    const REF                           = 'ref';

    const AUTH                          = 'auth';

    const POST_DATE                     = 'postdate';

    const TRANDATA                      = 'trandata';
}
