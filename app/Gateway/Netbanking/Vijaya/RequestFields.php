<?php

namespace RZP\Gateway\Netbanking\Vijaya;

class RequestFields
{
    const MERCHANT_CONSTANT = 'PID';
    const AMOUNT            = 'AMT';
    const MERCHANT_NAME     = 'MERCHANT_NAME';
    const MERCHANT_ID       = 'MID';
    const ITEM_CODE         = 'ITC';
    const CURRENCY          = 'CRN';
    const PAYMENT_ID        = 'PRN';
    const RETURN_URL        = 'RU';

    //For verify:
    const BANK_REFERENCE_NUMBER = 'BID';
}
