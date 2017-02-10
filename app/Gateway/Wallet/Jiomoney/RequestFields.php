<?php

namespace RZP\Gateway\Wallet\Jiomoney;

class RequestFields
{
    const MERCHANT_ID   = 'merchantid';
    const CLIENT_ID     = 'clientid';
    const CHANNEL       = 'channel';
    const CALLBACK_URL  = 'returl';
    const TOKEN         = 'token';
    const TRANSACTION   = 'transaction';
    const PAYMENT_ID    = 'extref';
    const TIMESTAMP     = 'timestamp';
    const TXN_TYPE      = 'txntype';
    const AMOUNT        = 'amount';
    const CURRENCY      = 'currency';
    const CHECKSUM      = 'checksum';
    const REFUND_INFO   = 'refundinfo';
    const SUBSCRIBER    = 'subscriber';
    const EMAIL         = 'email';
    const CONTACT       = 'mobilenumber';
    const CUSTOMER_NAME = 'customername';
    const APINAME       = 'apiname';
    const MODE          = 'mode';
    const REQUEST_ID    = 'request_id';
    const STARTDATETIME = 'startdatetime';
    const ENDDATETIME   = 'enddatetime';

    public static function getFormatted(string $prefix, string $field, string $delimiter = '.')
    {
        return $prefix . $delimiter . $field;
    }
}
