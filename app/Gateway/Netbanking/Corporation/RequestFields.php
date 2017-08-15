<?php

namespace RZP\Gateway\Netbanking\Corporation;

class RequestFields
{
    const CUSTOMER_ID          = 'CustID';
    const MERCHANT_CODE        = 'MerCD';
    const AMOUNT               = 'AMT';
    const PAYMENT_ID           = 'OTC';
    const MODE_OF_TRANSACTION  = 'MD';
    const FUND_TRANSFER        = 'TT';

    const VERIFY_DATA                   = 'data';
    const VERIFY_MERCHANT_CODE          = 'mercode';
    const VERIFY_PAYMENT_ID             = 'otcno';
    const VERIFY_AMOUNT                 = 'amt';
    const VERIFY_BANK_REF_NUMBER        = 'bankrefno';
    const VERIFY_MODE_OF_TRANSACTION    = 'md';
}
