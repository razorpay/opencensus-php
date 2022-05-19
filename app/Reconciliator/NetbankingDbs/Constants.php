<?php

namespace RZP\Reconciliator\NetbankingDbs;

class Constants
{
    const MERCHANT_ID          = 'Merchant ID';
    const MERCHANT_ORDER_ID    = 'Merchant Order id (RazorPay tran ref no.)';
    const BANK_REF_NO          = 'Bank Transaction ReferenceNo';
    const TXN_AMOUNT           = 'TransactionAmount';
    const ORDER_TYPE           = 'Order Type';
    const STATUS               = 'STATUS';
    const TXN_DATE             = 'TRANSACTION Date';
    const PAYMENT_ID           = 'Original RazorPay tran ref no.';
    const PAYMENT_BANK_REF_NO  = 'Original Bank Transaction ReferenceNo';


    const PAYMENT_COLUMN_HEADERS = [
        self::MERCHANT_ORDER_ID,
        self::BANK_REF_NO,
        self::TXN_AMOUNT,
        self::ORDER_TYPE,
        self::STATUS,
        self::TXN_DATE,
    ];
}
