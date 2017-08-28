<?php

namespace RZP\Gateway\Netbanking\Icici;

class RequestFields
{
    const MODE              = 'MD';
    const PAYEE_ID          = 'PID';
    const SPID              = 'SPID';
    const PAYMENT_ID        = 'PRN';
    const ITEM_CODE         = 'ITC';
    const AMOUNT            = 'AMT';
    const CURRENCY_CODE     = 'CRN';
    const RETURN_URL        = 'RU';
    const CONFIRMATION      = 'CG';
    const ACCOUNT_NO        = 'ACNO';
    const ENCRYPTED_STRING  = 'ES';
    const PAYMENT_DATE      = 'Pmt_Date';
    const SHOW_ON_SAME_PAGE = 'ShowOnSamePage';
}
