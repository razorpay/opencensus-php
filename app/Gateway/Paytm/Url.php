<?php

namespace RZP\Gateway\Paytm;

class Url
{
    const TEST_DOMAIN   = 'https://securegw-stage.paytm.in';
    const LIVE_DOMAIN   = 'https://securegw.paytm.in';

    const PAY           = '/theia/processTransaction';
    const VERIFY        = '/merchant-status/getTxnStatus';
    const REFUND        = '/refund/apply';
    const VERIFY_REFUND = '/v2/refund/status';
}
