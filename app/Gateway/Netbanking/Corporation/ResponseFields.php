<?php

namespace RZP\Gateway\Netbanking\Corporation;

class ResponseFields
{
    const MODE_OF_TRANSACTION = 'MD';
    const MERCHANT_CODE       = 'MerCD';
    const PAYMENT_ID          = 'OTC';
    const CUSTOMER_ID         = 'CustID';
    const AMOUNT              = 'AMT';
    const FUND_TRANSFER       = 'TT';
    const BANK_REF_NUMBER     = 'BRN';
    const STATUS              = 'Status';
    const ENCRYPTED_DATA      = 'data';

    const VERIFY_MERCHANT_CODE          = 'mercode';
    const VERIFY_PAYMENT_ID             = 'otcno';
    const VERIFY_AMOUNT                 = 'amt';
    const VERIFY_BANK_REF_NUMBER        = 'bankrefno';
    const VERIFY_RESULT                 = 'result';
    const VERIFY_RESULTMESSAGE          = 'resultmsg';
    const VERIFY_PAYMENT_DATE_TIME      = 'paydatetime';
}
