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

    // S2S fields
    const S2S_TRANSACTION_REF = 'transRefNo';
    const MOBILE_NUMBER       = 'MSISDN';
    const S2S_STATUS_CODE     = 'statusCode';
    const S2S_TRANS_ID        = 'mcomPgTransID';
    const DESCRIPTION         = 'description';
    const OTP_REF_NUMBER      = 'transRefNum';

    // otp fields
    const OTP_MOBILE_NUMBER   = 'msisdn';
}
