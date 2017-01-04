<?php

namespace RZP\Gateway\Netbanking\Axis;

class ResponseFields
{
    const MERCHANT_UNIQUE_REFERENCE = 'PRN';
    const ITEM_CODE                 = 'ITC';
    const CURRENCY_CODE             = 'CRN';
    const AMOUNT                    = 'AMT';
    const STATUS                    = 'PAID';
    const BANK_REFERENCE_ID         = 'BID';
    const FLAG                      = 'STATFLG';
    const ENCRYPTED_STRING          = 'QR';
    const PAYEE_ID                  = 'PAYEEID';
    const DATE                      = 'PaymentDate';
    const PAYMENT_STATUS            = 'PaymentStatus';
}
