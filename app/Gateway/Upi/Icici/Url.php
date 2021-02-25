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
    const PAY_V3       = '/api/MerchantAPI/UPI/v3/QR/%s';

    // New endpoints can be referenced from
    // https://docs.google.com/spreadsheets/d/1kgw9UYHXY_0-iliw-aAqllWzjCBxclXL/edit#gid=1294257586

    const TEST_V2_DOMAIN    = 'https://apibankingonesandbox.icicibank.com';
    const LIVE_V2_DOMAIN    = 'https://apibankingone.icicibank.com';

    //here %s is for merchant id
    const PAY_V2            = '/api/MerchantAPI/UPI/v0/QR/%s';
    const AUTHENTICATE_V2   = '/api/MerchantAPI/UPI/v0/CollectPay3/%s';
    const VERIFY_V2         = '/api/MerchantAPI/UPI/v0/CallbackStatus/%s';
    const REFUND_V2         = '/api/MerchantAPI/UPI/v0/Refund/%s';
    const PAY_V3_V2         = '/api/MerchantAPI/UPI/v0/QR3/%s';

}
