<?php

namespace RZP\Gateway\Wallet\Olamoney;

class RequestFields
{
    const COMMAND                   = 'command';
    const ACCESS_TOKEN              = 'accessToken';
    const UNIQUE_ID                 = 'uniqueId';
    const COMMENTS                  = 'comments';
    const UDF                       = 'udf';
    const RETURN_URL                = 'returnUrl';
    const NOTIFICATION_URL          = 'notificationUrl';
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';
    const COUPON_CODE               = 'couponCode';
    const UNIQUE_BILL_ID            = 'uniqueBillId';
    const TIMESTAMP                 = 'timestamp';
    const BALANCE_TYPE              = 'balanceType';
    const BALANCE_NAME              = 'balanceName';
    const SALE_ID                   = 'saleId';
    const OTP                       = 'otp';
    const HASH                      = 'hash';
    const BILL                      = 'bill';
    const PHONE                     = 'phone';
    const EMAIL                     = 'email';
    const USER_ACCESS_TOKEN         = 'userAccessToken';
    const MERCHANT_DISPLAY_NAME     = 'merchantDisplayName';
    const MERCHANT_REFERENCE_ID     = 'merchantReferenceId';
    const IS_CASHBACK_ATTEMPTED     = 'isCashbackAttempted';
    const IS_CASHBACK_SUCCESSFUL    = 'isCashbackSuccessful';
}
