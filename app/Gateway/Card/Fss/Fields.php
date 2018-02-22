<?php

namespace RZP\Gateway\Card\Fss;

class Fields
{
    // Card number
    const CARD                          = 'card';

    // Card verification number
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

    // Merchant TrackId which is our paymentid/refundid
    const TRACK_ID                      = 'trackid';

    const ERROR_URL                     = 'errorURL';

    const RESPONSE_URL                  = 'responseURL';

    // Terminal Id
    const ID                            = 'id';

    const PASSWORD                      = 'password';

    const REQUEST                       = 'request';

    const BANK_CODE                     = 'bankCode';

    const UDF5                          = 'udf5';

    const UDF3                          = 'udf3';

    const TRAN_DATA                     = 'trandata';

    const TRANPORTAL_ID                 = 'tranportalId';

    const GATEWAY_PAYMENT_ID            = 'paymentid';

    const RESULT                        = 'result';

    // Gateway PaymentId
    const PAY_ID                        = 'payid';

    const AUTH_RES_CODE                 = 'authrescode';

    const TRANSACTION_ID                = 'transid';

    const LANGUAGE_ID                   = 'langid';

    const ACQUIRER                      = 'acquirer';

    // Response Fields
    const ERROR_TEXT                    = 'error_text';

    const ERROR                         = 'error';

    const GATEWAY_ERROR_TEXT            = 'ErrorText';

    const ACTIONVPAS                    = 'actionVPAS';

    const TRAN_ID                       = 'tranid';

    const REF                           = 'ref';

    const AUTH                          = 'auth';

    const POST_DATE                     = 'postdate';

    // Encrypted data from gateway.
    const TRANDATA                      = 'trandata';

    const PARAM                         = 'param';
}
