<?php

namespace RZP\Gateway\Netbanking\Icici;

class ResponseFields
{
    const PAID             = 'PAID';
    const BANK_PAYMENT_ID  = 'BID';
    const PAYMENT_ID       = 'PRN';
    const ITEM_CODE        = 'ITC';
    const AMOUNT           = 'AMT';
    const CURRENCY_CODE    = 'CRN';
    const CURRENCY         = 'CURRENCY';
    const PAYMENT_DATE     = 'PMTDATE';
    const STATUS           = 'STATUS';
    const LC_STATUS        = 'status';

    const BILL_REF_NUM     = 'BILL REF NUMBER';
    const PAYMENTID        = 'PAYMENTID';
    const CONSUMER_CODE    = 'CONSUMER CODE';

    const US_BILL_REF_NUM  = 'BILL_REF_NUMBER';
    const US_CONSUMER_CODE = 'CONSUMER_CODE';
    const UC_AMOUNT        = 'AMOUNT';
}
