<?php

namespace RZP\Gateway\Netbanking\Allahabad;

class RequestFields
{
    const ACTION               = 'Action.ShoppingMall.Login.Init';
    const BANK_ID              = 'BankId';
    const MODE_OF_PAYMENT      = 'MD';
    const PAYEE_ID             = 'PID';
    const ITEM_CODE            = 'ITC';
    const PRODUCT_REF_NUMBER   = 'PRN';
    const AMOUNT               = 'AMT';
    const CURRENCY             = 'CRN';
    const RETURN_URL           = 'RU';
    const CG                   = 'CG';
    const LANGUAGE_ID          = 'USER_LANG_ID';
    const USER_TYPE            = 'UserType';
    const APP_TYPE             = 'AppType';
    const MERCHANT_CODE        = 'MeCode';
    const ACCOUNT_NUMBER       = 'AccountNo';
    const STATFLG              = 'STATFLG';
    const BANK_TRANSACTION_ID  = 'BID';
}
