<?php

namespace RZP\Gateway\Netbanking\Pnb;

class ResponseFields
{
    // required
    const CHALLAN_NUMBER      = 'cin';
    const BANK_TRANSACTION_ID = 'banktransid';
    const BANK_PAYMENT_DATE   = 'bankdate';
    const BANK_AMOUNT_PAID    = 'bankamount';
    const BANK_PAYMENT_STATUS = 'bankstatus';
    const ITEM_CODE           = 'ITC';

    // optional
    const STATUS_DESCRIPTON   = 'statusdesc';

    // encryption
    const CHECKSUM            = 'checksum';
    const ENCDATA             = 'encdata';
}
