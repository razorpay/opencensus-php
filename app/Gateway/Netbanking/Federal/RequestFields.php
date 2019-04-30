<?php

namespace RZP\Gateway\Netbanking\Federal;

class RequestFields
{
    const ACTION          = 'Action.ShoppingMall.Login.Init';
    const BANK_ID         = 'BankId';
    const MODE            = 'MD';
    const PAYEE_ID        = 'PID';
    const PAYMENT_ID      = 'PRN';
    const ITEM_CODE       = 'ITC';
    const AMOUNT          = 'AMT';
    const CURRENCY        = 'CRN';
    const LANGUAGE_ID     = 'USER_LANG_ID';
    const STATE_FLAG      = 'STATFLG';
    const USER_TYPE       = 'UserType';
    const APP_TYPE        = 'AppType';
    const CONFIRMATION    = 'CG';
    const BANK_PAYMENT_ID = 'BID';
    const RETURN_URL      = 'RU';
    const HASH            = 'HASH';
}
