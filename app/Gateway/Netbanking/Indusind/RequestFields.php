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
    const USER_TYPE                = 'UserType';
    const ACCOUNT_NUMBER           = 'ACID';

    // verify fields
    const PAYMENT_TYPE             = 'STATFLG';
    const BANK_REFERENCE_ID        = 'BID';
}
