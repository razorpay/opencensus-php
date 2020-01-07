<?php

namespace RZP\Gateway\Wallet\Olamoney;

class ResponseFields
{
    const TYPE                      = 'type';
    const STATUS                    = 'status';
    const MERCHANT_BILL_ID          = 'merchantBillId';
    const TRANSACTION_ID            = 'transactionId';
    const AMOUNT                    = 'amount';
    const COMMENTS                  = 'comments';
    const UDF                       = 'udf';
    const TIMESTAMP                 = 'timestamp';
    const HASH                      = 'hash';
    const MESSAGE                   = 'message';
    const ERROR_CODE                = 'errorCode';
    const UNIQUE_BILL_ID            = 'uniqueBillId';
    const IS_CASHBACK_ATTEMPTED     = 'isCashbackAttempted';
    const IS_CASHBACK_SUCCESSFUL    = 'isCashbackSuccessful';
    const ACCESS_TOKEN              = 'accessToken';
    const REFRESH_TOKEN             = 'refreshToken';
    const BALANCE_TYPE              = 'balanceType';
    const GLOBAL_MERCHANT_ID        = 'globalMerchantId';
    const ELIGIBILITY               = 'eligibility';
    const STATUS_CODE               = 'status_code';

    const VERIFY_FAILED_STATUS      = [Status::INITIATED, Status::FAILED];

    const REFUND_SUCCESS_STATUS     = Status::SUCCESS;
    const SALT                      = 'SALT';
    const X_TENANT                  = 'xtenant';
    const X_TENANT_KEY              = 'xtenantKey';
    const X_AUTH_KEY                = 'xauthKey';
    const BODY                      = 'body';
}
