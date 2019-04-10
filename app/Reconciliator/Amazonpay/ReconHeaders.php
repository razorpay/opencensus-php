<?php

namespace RZP\Reconciliator\Amazonpay;

class ReconHeaders
{
    const TRANSACTION_POSTED_DATE       = 'TransactionPostedDate';
    const SETTLEMENT_ID                 = 'SettlementId';
    const AMAZON_TRANSACTION_ID         = 'AmazonTransactionId';
    const MERCHANT_ORDER_ID             = 'MerchantOrderId';
    const TRANSACTION_TYPE              = 'TransactionType';
    const AMAZON_ORDER_REFERENCE_ID     = 'AmazonOrderReferenceId';
    const MERCHANT_ORDER_REFERENCE_ID   = 'MerchantOrderReferenceId';
    const STORE_NAME                    = 'StoreName';
    const CURRENCY_CODE                 = 'CurrencyCode';
    const TRANSACTION_DESCRIPTION       = 'TransactionDescription';
    const ORDER_AMOUNT                  = 'OrderAmount';
    const ORDER_COMMISSION              = 'OrderCommission';
    const TRANSACTION_FIXED_FEE         = 'TransactionFixedFee';
    const TOTAL_TRANSACTION_FEE         = 'TotalTransactionFee';
    const NET_TRANSACTION_AMOUNT        = 'NetTransactionAmount';
    const GST                           = 'gst';

    const COLUMN_HEADERS = [
        self::TRANSACTION_POSTED_DATE,
        self::SETTLEMENT_ID,
        self::AMAZON_TRANSACTION_ID,
        self::MERCHANT_ORDER_REFERENCE_ID,
        self::TRANSACTION_TYPE,
        self::AMAZON_ORDER_REFERENCE_ID,
        self::MERCHANT_ORDER_ID,
        self::STORE_NAME,
        self::CURRENCY_CODE,
        self::TRANSACTION_DESCRIPTION,
        self::ORDER_AMOUNT,
        self::ORDER_COMMISSION,
        self::TRANSACTION_FIXED_FEE,
        self::TOTAL_TRANSACTION_FEE,
        self::NET_TRANSACTION_AMOUNT,
        self::GST
    ];
}
