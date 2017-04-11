<?php

namespace RZP\Gateway\Wallet\Mpesa;

class ResponseFields
{
    // Redirect Auth Flow Fields
    const COM_TRANSACTION_ID     = 'mcompgtransid';
    const TRANSACTION_REFERENCE  = 'transrefno';
    const STATUS_CODE            = 'statuscode';
    const REASON                 = 'reason';
    const TRANSACTION_AMOUNT     = 'txnAmt';

    // Verify fields
    const VERIFY_TRANSACTION_REF = 'transRefNo';
    const MOBILE_NUMBER          = 'MSISDN';
    const VERIFY_STATUS_CODE     = 'statusCode';
    const VERIFY_TRANS_ID        = 'mcomPgTransID';
}
