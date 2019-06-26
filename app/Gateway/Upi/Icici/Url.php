<?php

namespace RZP\Gateway\Upi\Icici;

class Url
{
    const TEST_DOMAIN  = 'https://apigwuat.icicibank.com:8443';
    const LIVE_DOMAIN  = 'https://api.icicibank.com:8443';

    //here %s is for merchant id
    const PAY          = '/api/MerchantAPI/UPI/v1/QR/%s';
    const AUTHENTICATE = '/api/MerchantAPI/UPI/v3/CollectPay/%s';
    const VERIFY       = '/api/MerchantAPI/UPI/v1/CallbackStatus/%s';
    const REFUND       = '/api/MerchantAPI/UPI/v1/Refund/%s';
}
