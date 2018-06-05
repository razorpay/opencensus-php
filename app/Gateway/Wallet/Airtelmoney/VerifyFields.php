<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

class VerifyFields
{
    // request
    const AMOUNT                    = 'amount';
    const SESSION_ID                = 'feSessionId';
    const REQUEST                   = 'request';

    // response
    const TRANSACTION_AMOUNT        = 'txnAmount';
    const CODE                      = 'code';
    const MESSAGE_TEXT              = 'messageText';
    const ERROR_CODE                = 'errorCode';
    const STATUS                    = 'status';
    const TRANSACTION               = 'txns';
    const TRANSACTION_ID            = 'txnid';

    // common
    const MERCHANT_ID               = 'merchantId';
    const HASH                      = 'hash';
    const TRANSACTION_REFERENCE_NO  = 'txnRefNO';
    const TRANSACTION_DATE          = 'txnDate';
}
