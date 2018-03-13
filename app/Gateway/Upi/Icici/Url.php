<?php

namespace RZP\Gateway\Upi\Icici;

class Url
{
    const TEST_DOMAIN  = 'https://apigwuat.icicibank.com:8443';
    const LIVE_DOMAIN  = 'https://api.icicibank.com:8443';

    //here %s is for merchant id
    const PAY          = '/api/MerchantAPI/UPI/v1/QR/%s';
    const AUTHORIZE    = '/api/MerchantAPI/UPI/v2/CollectPay/%s';
    const VERIFY       = '/api/MerchantAPI/UPI/v2/TransactionStatus/%s';
    const REFUND       = '/api/MerchantAPI/UPI/v1/Refund/%s';
}
