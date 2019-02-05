<?php

namespace RZP\Gateway\Paytm;

class Url
{
    const TEST_DOMAIN   = 'https://securegw-stage.paytm.in';
    const LIVE_DOMAIN   = 'https://securegw.paytm.in';

    const PAY           = '/theia/processTransaction';
    const VERIFY        = '/oltp/HANDLER_INTERNAL/TXNSTATUS';
    const REFUND        = '/oltp/HANDLER_INTERNAL/REFUND';
}
