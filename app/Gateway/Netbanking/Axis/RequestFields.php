<?php

namespace RZP\Gateway\Netbanking\Axis;

class RequestFields
{
    const AUTHENTICATION_MENU_ID    = 'AuthenticationFG.MENU_ID'; // CIMSHP
    const AUTHENTICATION_CALL_MODE  = 'AuthenticationFG.CALL_MODE'; // 2
    const CATEGORY_ID               = 'CATEGORY_ID'; // IRCSM for encrypted
    const MERCHANT_UNIQUE_REFERENCE = 'PRN';
    const BANK_ACCOUNT_NUMBER       = 'PRN1';
    const PAYEE_ID                  = 'PID';
    const MODE_OF_OPERATION         = 'MD';
    const ITEM_CODE                 = 'ITC';
    const CURRENCY_CODE             = 'CRN';
    const RETURN_URL                = 'RU';
    const ENCRYPTED_STRING          = 'qs';
    const AMOUNT                    = 'AMT';
    const RESPONSE                  = 'RESPONSE';
    const CONFIRMATION              = 'CG';
}
