<?php

namespace RZP\Gateway\Netbanking\Indusind;

class RequestFields
{
    const MERCHANT_REFERENCE       = 'PRN';
    const BANK_ACCOUNT_NUMBER      = 'PRN1';
    const PAYEE_ID                 = 'PID';
    const MODE                     = 'MD';
    const ITEM_CODE                = 'ITC';
    const CURRENCY_CODE            = 'CRN';
    const RETURN_URL               = 'RU';
    const ENCRYPTED_STRING         = 'QS';
    const AMOUNT                   = 'AMT';
    const RESPONSE                 = 'RESPONSE';
    const CONFIRMATION             = 'CG';
    const DATE                     = 'DATE';

    // verify fields
    const VERIFY_PAYEE_ID          = 'payeeid';
    const VERIFY_ITC               = 'itc';
    const VERIFY_PRN               = 'prn';
    const VERIFY_DATE              = 'date';
    const VERIFY_AMT               = 'amt';
}
