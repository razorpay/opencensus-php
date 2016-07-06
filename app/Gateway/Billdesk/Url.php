<?php

namespace RZP\Gateway\Billdesk;

class Url
{
    const TEST_DOMAIN   = 'https://pgi.billdesk.com';
    const LIVE_DOMAIN   = 'https://pgi.billdesk.com';

    const AUTHORIZE     = '/pgidsk/PGIMerchantRequestHandler';
    const REFUND        = '/pgidsk/PGIRefundController';
    const VERIFY        = '/pgidsk/PGIQueryController';
}
