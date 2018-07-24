<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Gateway\Upi\Base\Entity;

class Fields
{
    const MERCH_ID = 'merchId';
    const MERCH_CHAN_ID = 'merchChanId';
    const UNQ_TXN_ID = 'unqTxnId';
    const UNQ_CUST_ID = 'unqCustId';
    const AMOUNT = 'amount';
    const TXN_DTL = 'txnDtl';
    const CURRENCY = 'currency';
    const ORDER_ID = 'orderId';
    const CUSTOMER_VPA = 'customerVpa';
    const EXPIRY = 'expiry';
    const S_ID = 'sId';
    const TXN_REFUND_ID = 'txnRefundId';
    const MOB_NO = 'mobNo';
    const TXN_REFUND_AMOUNT = 'txnRefundAmount';
    const REFUND_REASON = 'refundReason';
    const CHECKSUM = 'checkSum';
    const CODE = 'code';
    const RESULT = 'result';
    const DATA = 'data';
    const VPA_STATUS = 'vpa_status';
    const MERCHANT_TRANSACTION_ID = 'merchantTransactionId';
    const TRANSACTION_TIMESTAMP = 'transactionTimestamp';
    const TRANSACTION_AMOUNT = 'transactionAmount';
    const GATEWAY_TRANSACTION_ID = 'gatewayTransactionId';
    const GATEWAY_RESPONSE_CODE = 'gatewayResponseCode';
    const GATEWAY_RESPONSE_MESSAGE = 'gatewayResponseMessage';
    const RRN = 'rrn';
    const W_COLLECT_TXN_ID = 'wCollectTxnId';
    const TXN_TIME = 'txnTime';
    const TXN_AMOUNT = 'txnAmount';
    const DEBIT_ACCOUNT_NUM = 'debitAccountNum';
    const DEBIT_IFSC_CODE = 'debitIfscCode';

    /**
     * These are the expected field orders
     * for the response we get from the API
     *
     * @see https://drive.google.com/drive/u/0/folders/0B1MTSXtR53PfYldqNUIyLXlnSjA
     */

    const TOKEN_GENERATION = [
        self::CODE,
        self::RESULT,
        self::DATA,
    ];

    const COLLECT = [
        self::CODE,
        self::RESULT,
        self::DATA,
    ];

    const CALLBACK_ENCRYPTED = [
        self::DATA,
    ];

    const CALLBACK = [
        self::CUSTOMER_VPA,
        self::MERCH_ID,
        self::MERCH_CHAN_ID,
        self::MERCHANT_TRANSACTION_ID,
        self::TRANSACTION_TIMESTAMP,
        self::TRANSACTION_AMOUNT,
        self::GATEWAY_TRANSACTION_ID,
        self::GATEWAY_RESPONSE_CODE,
        self::GATEWAY_RESPONSE_MESSAGE,
        self::RRN,
        self::CHECKSUM,
    ];

    const CHECK_TRANSACTION_STATUS = [
        self::MERCH_ID,
        self::MERCH_CHAN_ID,
        self::UNQ_TXN_ID,
        self::CHECKSUM,
    ];
}