<?php

namespace RZP\Reconciliator\Amazonpay;

class ReconHeaders
{
    const SETTLEMENT_ID                 = 'SettlementId';
    const MERCHANT_ORDER_ID             = 'MerchantOrderId';
    const TRANSACTION_TYPE              = 'TransactionType';
    const AMAZON_ORDER_REFERENCE_ID     = 'AmazonOrderReferenceId';
    const MERCHANT_ORDER_REFERENCE_ID   = 'MerchantOrderReferenceId';
    const STORE_NAME                    = 'StoreName';
    const CURRENCY_CODE                 = 'CurrencyCode';
    const TRANSACTION_DESCRIPTION       = 'TransactionDescription';
    const ORDER_AMOUNT                  = 'OrderAmount';
    const ORDER_COMMISSION              = 'OrderCommission';
    const NET_TRANSACTION_AMOUNT        = 'NetTransactionAmount';
    const GST                           = 'GST';
    const MERCHANT_ID                   = 'MerchantId';
    const MERCHANT_NAME                 = 'MerchantName';
    const ORDER_DATE                    = 'OrderDate';
    const ORDER_TIME                    = 'OrderTime';
    const ORDER_ID                      = 'OrderId';
    const ARN                           = 'ARN';
    const UTR                           = 'UTR';
    const REFUND_DATE                   = 'RefundDate';
    const REFUND_TIME                   = 'RefundTime';
    const STORE_ID                      = 'StoreId';
    const TOTAL_COMMISSION              = 'TotalCommission';
    const NET_SETTLEMENT_AMOUNT         = 'NetSettlementAmount';
    const MERCHANT_STORE_ID             = 'MerchantStoreId';

    const COLUMN_HEADERS = [
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
        self::ORDER_DATE,
        self::ORDER_TIME,
        self::ORDER_ID,
        self::MERCHANT_ORDER_ID,
        self::AMAZON_ORDER_REFERENCE_ID,
        self::MERCHANT_ORDER_REFERENCE_ID,
        self::TRANSACTION_TYPE,
        self::TRANSACTION_DESCRIPTION,
        self::ARN,
        self::UTR,
        self::REFUND_DATE,
        self::REFUND_TIME,
        self::CURRENCY_CODE,
        self::STORE_NAME,
        self::STORE_ID,
        self::ORDER_AMOUNT,
        self::ORDER_COMMISSION,
        self::GST,
        self::TOTAL_COMMISSION,
        self::NET_SETTLEMENT_AMOUNT,
        self::SETTLEMENT_ID,
        self::MERCHANT_STORE_ID,
    ];
}
