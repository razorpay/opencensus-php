<?php

namespace RZP\Gateway\Upi\Hdfc;

class Url
{
    const TEST_DOMAIN       = 'https://upitest.hdfcbank.com';
    const LIVE_DOMAIN       = 'https://upitest.hdfcbank.com';

    const AUTHORIZE    = '/upi/meTransCollectSvc';

    // TODO: placeholders for now
    const VERIFY       = '/upi/transactionStatusQuery';
    const REFUND       = '/refund';
}
