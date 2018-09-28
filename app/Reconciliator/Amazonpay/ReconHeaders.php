<?php

namespace RZP\Reconciliator\Amazonpay;

class ReconHeaders
{
    const TRANSACTION_POSTED_DATE       = 'TransactionPostedDate';
    const SETTLEMENT_ID                 = 'SettlementId';
    const AMAZON_TRANSACTION_ID         = 'AmazonTransactionId';
    const SELLER_REFERENCE_ID           = 'SellerReferenceId';
    const TRANSACTION_TYPE              = 'TransactionType';
    const AMAZON_ORDER_REFERENCE_ID     = 'AmazonOrderReferenceId';
    const SELLER_ORDER_ID               = 'SellerOrderId';
    const STORE_NAME                    = 'StoreName';
    const CURRENCY_CODE                 = 'CurrencyCode';
    const TRANSACTION_DESCRIPTION       = 'TransactionDescription';
    const TRANSACTION_AMOUNT            = 'TransactionAmount';
    const TRANSACTION_PERCENTAGE_FEE    = 'TransactionPercentageFee';
    const TRANSACTION_FIXED_FEE         = 'TransactionFixedFee';
    const TOTAL_TRANSACTION_FEE         = 'TotalTransactionFee';
    const NET_TRANSACTION_AMOUNT        = 'NetTransactionAmount';

    const COLUMN_HEADERS = [
        self::TRANSACTION_POSTED_DATE,
        self::SETTLEMENT_ID,
        self::AMAZON_TRANSACTION_ID,
        self::SELLER_REFERENCE_ID,
        self::TRANSACTION_TYPE,
        self::AMAZON_ORDER_REFERENCE_ID,
        self::SELLER_ORDER_ID,
        self::STORE_NAME,
        self::CURRENCY_CODE,
        self::TRANSACTION_DESCRIPTION,
        self::TRANSACTION_AMOUNT,
        self::TRANSACTION_PERCENTAGE_FEE,
        self::TRANSACTION_FIXED_FEE,
        self::TOTAL_TRANSACTION_FEE,
        self::NET_TRANSACTION_AMOUNT
    ];
}
