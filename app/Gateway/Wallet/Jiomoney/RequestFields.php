<?php

namespace RZP\Gateway\Wallet\Jiomoney;

class RequestFields
{
    const MERCHANT_ID  = 'merchantid';
    const CLIENT_ID    = 'clientid';
    const CHANNEL      = 'channel';
    const CALLBACK_URL = 'returl';
    const TOKEN        = 'token';
    const TRANSACTION  = 'transaction';
    const PAYMENT_ID   = 'extref';
    const TIMESTAMP    = 'timestamp';
    const TXN_TYPE     = 'txntype';
    const AMOUNT       = 'amount';
    const CURRENCY     = 'currency';
    const CHECKSUM     = 'checksum';
    const REFUND_INFO  = 'refundinfo';
}
