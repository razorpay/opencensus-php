<?php

namespace RZP\Gateway\Atom;

class Url
{
    const TEST_DOMAIN         = 'https://paynetzuat.atomtech.in';
    const LIVE_DOMAIN         = 'https://payment.atomtech.in';

    const AUTHORIZE           = '/paynetz/epi/fts';
    const VERIFY              = '/paynetz/vfts';
    const REFUND              = '/paynetz/rfts';
    const VERIFY_REFUND_TEST  = '/refundstatus/refundStatus';
    const VERIFY_REFUND_LIVE  = '/refundstatus/rsfts';
}
