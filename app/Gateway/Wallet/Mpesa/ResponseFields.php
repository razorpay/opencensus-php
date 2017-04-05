<?php

namespace RZP\Gateway\Wallet\Mpesa;

class ResponseFields
{
    const COM_TRANSACTION_ID    = 'mcompgtransid';
    const TRANSACTION_REFERENCE = 'transrefno';
    const STATUS_CODE           = 'statuscode';
    const REASON                = 'reason';
    const TRANSACTION_AMOUNT    = 'txnAmt';
}
