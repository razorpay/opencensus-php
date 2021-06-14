<?php

namespace RZP\Gateway\Netbanking\Pnb;

class RequestFields
{
    // Optional
    const USER_NAME       = 'name';
    const ADDRESS         = 'address';
    const EMAIL           = 'email';
    const PHONE_NUMBER    = 'phone';
    const REMARK          = 'remark';
    const ACCOUNT_NUMBER  = 'SHP_ACCT_NUM';

    // Required
    const CHALLAN_NUMBER  = 'cin';
    const MERCHANT_DATE   = 'MerchantDate';
    const MERCHANT_AMOUNT = 'MerchantAmt';
    const RETURN_URL      = 'RU';
    const ITEM_CODE       = 'ITC';

    // Encryption
    const CHECKSUM        = 'checksum';
    const ENCDATA         = 'encdata';
}
