<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

class Url
{
    const TEST_DOMAIN    = 'https://buddyuat.sbi.co.in/';
    const LIVE_DOMAIN    = 'https://erupee.sbi.co.in/';

    const AUTHORIZE      = 'mmgw-tls/merchant/page/paynow';

    const REFUND         = 'mmgw-tls/merchant/api/refund';
    const VERIFY         = 'mmgw-tls/merchant/api/status';
    const PAYMENT_STATUS = 'mmgw-tls/merchant/api/status';
}
