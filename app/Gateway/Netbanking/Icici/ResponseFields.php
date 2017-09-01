<?php

namespace RZP\Gateway\Netbanking\Icici;

class ResponseFields
{
    /**
     * Tells us whether the payment was a success. Can be a Y or N.
     */
    const PAID             = 'PAID';
    const BANK_PAYMENT_ID  = 'BID';
    const PAYMENT_ID       = 'PRN';
    const ITEM_CODE        = 'ITC';
    const AMOUNT           = 'AMT';
    const CURRENCY_CODE    = 'CRN';
    const CURRENCY         = 'CURRENCY';
    const PAYMENT_DATE     = 'PMTDATE';

    /**
     * Gives us more information on the success / failure case.
     */
    const STATUS           = 'STATUS';
    const STATUS_LC        = 'status';

    const BILL_REF_NUM     = 'BILL REF NUMBER';
    const PAYMENTID        = 'PAYMENTID';
    const CONSUMER_CODE    = 'CONSUMER CODE';

    const US_BILL_REF_NUM  = 'BILL_REF_NUMBER';
    const US_CONSUMER_CODE = 'CONSUMER_CODE';
    const UC_AMOUNT        = 'AMOUNT';

    const SI_REFERENCE_ID    = 'RID';
    const SI_AUTO_PAY_AMOUNT = 'AUTO_PAY_AMOUNT';
    const SI_SCHEDULE_ID     = 'SCHEDULEID';
    const SI_STATUS          = 'SCHSTATUS';
    const SI_MESSAGE         = 'SCHMSG';
}
