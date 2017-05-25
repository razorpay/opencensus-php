<?php

namespace RZP\Gateway\Upi\Icici;

class Url
{
    const TEST_DOMAIN       = 'https://apigwuat.icicibank.com:8443';
    const LIVE_DOMAIN       = 'https://api.icicibank.com:8443';

    /**
     * Yes, the live routes are different from the test routes
     * ICICI FTW!
     *
     * We handle this in getUrl()
     */
    const LIVE_AUTHORIZE    = '/api/MerchantAPI/UPI/v2/CollectPay/%s';
    const LIVE_VERIFY       = '/api/MerchantAPI/UPI/v2/TransactionStatus/%s';
    const LIVE_REFUND       = '/api/MerchantAPI/UPI/v1/Refund/%s';

    const TEST_AUTHORIZE    = '/api/MerchantAPI/UPI/v2/CollectPay/%s';
    const TEST_VERIFY       = '/api/MerchantAPI/UPI/v2/TransactionStatus/%s';

    //here %s is for merchant Id string
    const TEST_REFUND       = '/api/MerchantAPI/UPI/v1/Refund/%s';
}
