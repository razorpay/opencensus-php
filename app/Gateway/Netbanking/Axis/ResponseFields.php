<?php

namespace RZP\Gateway\Netbanking\Axis;

class ResponseFields
{
    const MERCHANT_REFERENCE  = 'PRN';
    const ITEM_CODE           = 'ITC';
    const CURRENCY_CODE       = 'CRN';
    const AMOUNT              = 'AMT';
    const STATUS              = 'PAID';
    const BANK_REFERENCE_ID   = 'BID';
    const FLAG                = 'STATFLG';
    const ENCRYPTED_STRING    = 'QR';
    const PAYEE_ID            = 'PAYEEID';
    const DATE                = 'PaymentDate';
    const PAYMENT_STATUS      = 'PaymentStatus';
    const VERIFY_RESPONSE_AMT = 'Amount';

    const PAID                = 'Paid';
    const TRAN_DATE_TIME      = 'TRANDATETIME';
}
