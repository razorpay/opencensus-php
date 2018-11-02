<?php

namespace RZP\Gateway\Netbanking\Idfc;

class Fields
{
    const MERCHANT_ID           = 'MID';
    const PAYMENT_ID            = 'PID';
    const AMOUNT                = 'AMT';
    const RETURN_URL            = 'RetURL';
    const TRANSACTION_TYPE      = 'TxnType';
    const ACCOUNT_NUMBER        = 'ACCNO';
    const PAYMENT_DESCRIPTION   = 'NAR';
    const CHANNEL               = 'Channel';
    const MERCHANT_CODE         = 'MCC';
    const TRANSACTION_CURRENCY  = 'CRN';
    const CHECKSUM              = 'Checksum';

    //Extra Fields in Callback Response
    const BANK_REFERENCE_NUMBER = 'BID';
    const RESPONSE_CODE         = 'ResponseCode';
    const RESPONSE_MESSAGE      = 'ResponseMsg';
    const PAYMENT_STATUS        = 'PAID';

    //Extra fields in Verify Response
    const STATUS_RESULT         = 'TxnStatus';
}
