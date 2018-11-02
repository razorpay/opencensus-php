<?php

namespace RZP\Gateway\Netbanking\Idfc;

class Url
{
    const LIVE_AUTHORIZE_DOMAIN = 'https://my.idfcbank.com';
    const LIVE_VERIFY_DOMAIN    = 'https://api.idfcbank.com:9333';

    const TEST_AUTHORIZE_DOMAIN = 'https://14.142.152.96';
    const TEST_VERIFY_DOMAIN    = 'https://ESBUAT1RTN0140.idfcbank.com:9222';

    const AUTHORIZE     = '/payment';
    const VERIFY        = '/razorpay/ecomverification';
}
