<?php

namespace RZP\Gateway\Netbanking\Pnb;

class ResponseFields
{
    // required
    const CHALLAN_NUMBER      = 'cin';
    const BANK_TRANSACTION_ID = 'BankTransID';
    const BANK_PAYMENT_DATE   = 'BankDate';
    const BANK_AMOUNT_PAID    = 'BankAmount';
    const BANK_PAYMENT_STATUS = 'BankStatus';
    const ITEM_CODE           = 'ITC';

    // optional
    const STATUS_DESCRIPTON   = 'StatusDesc';

    // encryption
    const CHECKSUM            = 'checksum';
    const ENCDATA             = 'encdata';
}
