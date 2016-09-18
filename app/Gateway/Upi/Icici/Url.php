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
    const LIVE_AUTHORIZE    = '/api/MerchantAPI/UPI/v1/CollectPay';
    const LIVE_VERIFY       = '/api/MerchantAPI/UPI/v1/TransactionStatus';

    const TEST_AUTHORIZE    = '/newCollectPay';
    const TEST_VERIFY       = '/newTransactionStatus';
}
