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
    const REQUEST_HEADER = 'request_header';
    const VERSION = 'version';
    const API_NAME = 'api_name';
    const PAYLOAD_DATA = 'payload_data';
}
