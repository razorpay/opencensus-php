<?php

namespace RZP\Gateway\Upi\Axis;

class Fields
{
    const MERCH_ID			= 'merchId';
    const MERCH_CHAN_ID		= 'merchChanId';
    const UNQ_TXN_ID		= 'unqTxnId';
    const UNQ_CUST_ID		= 'unqCustId';
    const AMOUNT			= 'amount';
    const TXN_DTL			= 'txnDtl';
    const CURRENCY			= 'currency';
    const ORDER_ID			= 'orderId';
    const CUSTOMER_VPA		= 'customerVpa';
    const EXPIRY			= 'expiry';
    const S_ID				= 'sId';
    const TXN_REFUND_ID		= 'txnRefundId';
    const MOB_NO			= 'mobNo';
    const TXN_REFUND_AMOUNT	= 'txnRefundAmount';
    const REFUND_REASON		= 'refundReason';
    const CHECKSUM			= 'checkSum';

    const VALIDATE_VPA  = [
        self::UNQ_TXN_ID,
        self::CUSTOMER_VPA,
        self::S_ID,
        self::TXN_DTL,
    ];
}

