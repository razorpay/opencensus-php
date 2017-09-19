<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

class ResponseFields
{
    const EXTERNAL_TRANSACTION_ID   = 'externalTransactionId';
    const ORDER_ID                  = 'orderId';
    const TRANSACTION_ID            = 'transactionId';
    const AMOUNT                    = 'amount';
    const FEE                       = 'fee';
    const STATUS_CODE               = 'statusCode';
    const ERROR_DESCRIPTION         = 'errorDescription';
    const PROCESSOR_ID              = 'processorId';

    // For refunds
    const TRACKING_ID               = 'trackingId';
    const REFUND_ID                 = 'refundId';
    const REFUNDED_AMOUNT           = 'refundedAmount';

    const MERCHANT_ID               = 'merchantId';
    const ENCRYPTED_DATA            = 'encryptedData';
}
