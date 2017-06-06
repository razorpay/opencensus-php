<?php

namespace RZP\Gateway\Wallet\Mpesa;

class ResponseFields
{
    // Redirect Auth Flow Fields
    const COM_TRANSACTION_ID    = 'mcompgtransid';
    const TRANSACTION_REFERENCE = 'transrefno';
    const STATUS_CODE           = 'statuscode';
    const REASON                = 'reason';
    const TRANSACTION_AMOUNT    = 'txnAmt';

    // S2S fields
    const S2S_TRANSACTION_REF   = 'transRefNo';
    const MOBILE_NUMBER         = 'MSISDN';
    const S2S_STATUS_CODE       = 'statusCode';
    const S2S_TRANS_ID          = 'mcomPgTransID';
    const DESCRIPTION           = 'description';
    const S2S_REF_NUMBER        = 'transRefNum';
    const LC_STATUS             = 'status';
    const RESPONSE_ID           = 'responseId';

    // otp fields
    const OTP_MOBILE_NUMBER     = 'msisdn';

    // Soap Call Response Keys
    const VALIDATE_CUSTOMER     = 'MCOMResponseStatus';
    const OTP_GENERATE          = 'McomOtpResponse';
    const UCF_RESPONSE          = 'Response';
    const LC_RESPONSE           = 'response';
}
