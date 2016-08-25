<?php

namespace RZP\Gateway\Ebs;

class ResponseConstants
{
    const RESPONSE_CODE         = 'ResponseCode';
    const RESPONSE_MESSAGE      = 'ResponseMessage';
    const DATE_CREATED          = 'DateCreated';
    const GATEWAY_PAYMENT_ID    = 'PaymentID';
    const MERCHANT_REF_NO       = 'MerchantRefNo';
    const AMOUNT                = 'Amount';
    const MODE                  = 'Mode';
    const DESCRIPTION           = 'Description';
    const IS_FLAGGED            = 'IsFlagged';
    const TRANSACTION_ID        = 'TransactionID';
    const PAYMENT_METHOD        = 'PaymentMethod';
    const REQUEST_ID            = 'RequestID';
    const ACTION                = 'Action';
    const ACCOUNT_ID            = 'AccountID';
    const SECRET_KEY            = 'SecretKey';
    const REFERENCE             = 'referenceNo';
    const ERROR_CODE            = 'errorCode';
    const ERROR                 = 'error';
    const REF_AMOUNT            = 'RefAmount';
    const REFUND_ID             = 'refund_id';
    const REFUND_REF_NO         = 'reference_no';
    const CURRENCY              = 'currency';
    const SECURE_HASH           = 'SecureHash';
    const RESPONSE              = 'response';
    const STATUS                = 'status';

    //
    // EBS API response Fields
    //
    const API_IS_FLAGGED        = 'isFlagged';
    const API_TRANSACTION_ID    = 'transactionId';
    const API_REFERENCE_ID      = 'paymentId';
    const API_TRANSACTION_TYPE  = 'transactionType';
}
