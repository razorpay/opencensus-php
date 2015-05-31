<?php

namespace Gateway\Billdesk;

class Url
{
    const TEST_DOMAIN   = 'https://pguat.paytm.com';
    const LIVE_DOMAIN   = 'https://secure.paytm.in';

    const PAY           = '/oltp-web/processTransaction';
    const QUERY         = '/oltp/HANDLER_INTERNAL/TXNSTATUS';
    const REFUND        = '/oltp/HANDLER_INTERNAL/REFUND';
}