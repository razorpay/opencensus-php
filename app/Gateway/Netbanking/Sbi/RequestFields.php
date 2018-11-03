<?php

namespace RZP\Gateway\Netbanking\Sbi;

class RequestFields
{
    // Authorize request fields
    const REF_NO        = 'ref_no';      //razorpay payment id

    const AMOUNT        = 'amount';     // amount

    const PAYMENT_ID    = 'payment_id'; // payment_id

    const REDIRECT_URL  = 'Redt_url';

    const CANCEL_URL    = 'Cncl_url';

    const CHECKSUM      = 'checkSum';

    // Authorize query params
    const ENCDATA      = 'encdata';       // encrypted request string

    const MERCHANT_CODE = 'merchant_code'; // gateway merchant id


    // Verify request fields
    const BANK_REF_NO = 'bank_ref_no';

}
