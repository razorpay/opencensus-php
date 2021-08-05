<?php

namespace RZP\Gateway\Upi\Axis;

class Fields
{
    const MERCH_ID                     = 'merchId';
    const MERCH_CHAN_ID                = 'merchChanId';
    const CALLBACK_MERCHANT_ID         = 'merchantId';
    const CALLBACK_MERCHANT_CHAN_ID    = 'merchantChannelId';
    const CREDIT_VPA                   = 'creditVpa';
    const UNQ_TXN_ID                   = 'unqTxnId';
    const UNQ_CUST_ID                  = 'unqCustId';
    const AMOUNT                       = 'amount';
    const TXN_DTL                      = 'txnDtl';
    const CURRENCY                     = 'currency';
    const ORDER_ID                     = 'orderId';
    const CUSTOMER_VPA                 = 'customerVpa';
    const EXPIRY                       = 'expiry';
    const S_ID                         = 'sId';
    const TXN_REFUND_ID                = 'txnRefundId';
    const VERIFY_REFUND_ORDER_ID       = 'Orderid';
    const MOB_NO                       = 'mobNo';
    const TXN_REFUND_AMOUNT            = 'txnRefundAmount';
    const REFUND_REASON                = 'refundReason';
    const CHECKSUM                     = 'checkSum';
    const CODE                         = 'code';
    const RESULT                       = 'result';
    const DATA                         = 'data';
    const VPA_STATUS                   = 'vpa_status';
    const MERCHANT_TRANSACTION_ID      = 'merchantTransactionId';
    const TRANSACTION_TIMESTAMP        = 'transactionTimestamp';
    const TRANSACTION_AMOUNT           = 'transactionAmount';
    const GATEWAY_TRANSACTION_ID       = 'gatewayTransactionId';
    const GATEWAY_RESPONSE_CODE        = 'gatewayResponseCode';
    const GATEWAY_RESPONSE_MESSAGE     = 'gatewayResponseMessage';
    const RRN                          = 'rrn';
    const W_COLLECT_TXN_ID             = 'wCollectTxnId';
    const TXN_TIME                     = 'txnTime';
    const TXN_AMOUNT                   = 'txnAmount';
    const ACCOUNT_NUM                  = 'accountNo';
    const ACCOUNT_NUM_TPV              = 'accountNumber';
    const IFSC_CODE                    = 'Ifsc';
    const IFSC_CODE_TPV                = 'ifsc';
    const TOKEN                        = 'Token';

    const CHECK_STATUS_MERCH_ID        = 'merchid';
    const CHECK_STATUS_MERCH_CHAN_ID   = 'merchchanid';
    const CHECK_STATUS_UNQ_TXN_ID      = 'tranid';
    const CHECK_STATUS_CHECKSUM        = 'checksum';
    const CHECK_STATUS_MOBILE_NO       = 'mobilenumber';
    const CHECK_STATUS_REF_ID          = 'refid';
    const CHECK_STATUS_DATE_TIME       = 'dateTime';
    const CHECK_STATUS_DEBIT_VPA       = 'debitVpa';
    const CHECK_STATUS_CREDIT_VPA      = 'creditVpa';
    const CHECK_STATUS_TXN_ID          = 'txnid';
    const STATUS                       = 'status';
    const REMARKS                      = 'remarks';

    const CALLBACK_STATUS_CODE         = 'callBackstatusCode';
    const CALLBACK_STATUS_DESCRIPTION  = 'callBackstatusDescription';
    const CALLBACK_TXN_ID              = 'callBacktxnId';
    const CALLBACK_URL                 = 'callBackUrl';
}
