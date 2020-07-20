<?php

namespace RZP\Gateway\Netbanking\Pnb;

class ResponseFields
{
    // payment response fields
    const CHALLAN_NUMBER      = 'cin';
    const BANK_TRANSACTION_ID = 'banktransid';
    const BANK_PAYMENT_DATE   = 'bankdate';
    const BANK_AMOUNT_PAID    = 'bankamount';
    const BANK_PAYMENT_STATUS = 'bankstatus';
    const ITEM_CODE           = 'ITC';

    // verify response fields
    const CHALLAN_NUMBER_VERIFY      = 'CIN';
    const BANK_TRANSACTION_ID_VERIFY = 'BankTransID';
    const BANK_PAYMENT_DATE_VERIFY   = 'BankDate';
    const BANK_AMOUNT_PAID_VERIFY    = 'BankAmount';
    const BANK_PAYMENT_STATUS_VERIFY = 'BankStatus';

    // optional
    const STATUS_DESCRIPTON   = 'statusdesc';

    // encryption
    const CHECKSUM            = 'checksum';
    const ENCDATA             = 'encdata';
}
