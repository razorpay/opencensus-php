<?php

namespace RZP\Gateway\Netbanking\Pnb;

class Url
{
    // Todo : add Live domain url

    const LIVE_DOMAIN   = '';
    const TEST_DOMAIN   = 'https://oibapi.northakross.in';
    const REFUND_DOMAIN = 'https://oib.northakross.in';

    const AUTHORIZE     = '/v2/paymentseamlessrequest';
    const VERIFY        = '/v2/paymentstatus';
    const REFUND        = '/v2/refundrequest';
    const VERIFY_REFUND = '/v2/refundstatus';
}
