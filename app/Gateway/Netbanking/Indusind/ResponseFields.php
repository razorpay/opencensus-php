<?php

namespace RZP\Gateway\Netbanking\Indusind;

class ResponseFields
{
    const MERCHANT_REFERENCE  = 'PRN';
    const ITEM_CODE           = 'ITC';
    const CURRENCY_CODE       = 'CRN';
    const AMOUNT              = 'AMT';
    const PAID                = 'PAID';
    const BANK_REFERENCE_ID   = 'BID';
    const FLAG                = 'STATFLG';
    const ENCRYPTED_STRING    = 'RQS';
    const PAYEE_ID            = 'PAYEEID';
    const DATE                = 'PaymentDate';
    const PAYMENT_STATUS      = 'PaymentStatus';
    const VERIFY_RESPONSE_AMT = 'Amount';
    const STATUS              = 'status';
    const VERIFICATION        = 'VERIFICATION';
}
