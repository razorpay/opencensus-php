<?php

namespace RZP\Gateway\Aeps\Icici;

class Url
{
    const TEST_DOMAIN_URL   = '203.199.32.86';
    const LIVE_DOMAIN_URL   = '203.189.92.113';

    const TEST_DOMAIN_PORT  = '4443';
    const LIVE_DOMAIN_PORT  = '4404';

    const TEST_REFUND_URL = 'https://apigwuat.icicibank.com:8443/api/UPIStack/v1/PayRequestGlobalOutward/RazorPay';
    const LIVE_REFUND_URL = 'https://api.icicibank.com:8443/api/UPIStack/v1/PayRequestGlobalOutward/RazorPay';
}
