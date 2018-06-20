<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

class RefundFields
{
    // Request
    const REQUEST             = 'request';

    // Response
    const ERROR_CODE          = 'errorCode';
    const MESSAGE_TEXT        = 'messageText';
    const CODE                = 'code';
    const STATUS              = 'status';

    // Common
    const HASH                = 'hash';
    const AMOUNT              = 'amount';
    const MERCHANT_ID         = 'merchantId';
    const SESSION_ID          = 'feSessionId';
    const TRANSACTION_ID      = 'txnId';
    const TRANSACTION_DATE    = 'txnDate';
}
