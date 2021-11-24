<?php

namespace RZP\Gateway\Netbanking\Hdfc;

class Url
{
    const LIVE_DOMAIN   = 'https://netbanking.hdfcbank.com';
    const TEST_DOMAIN   = 'https://flexatuat.hdfcbank.com';

    const PAY           = '/netbanking/merchant';
    const VERIFY        = '/netbanking/epi';
}
