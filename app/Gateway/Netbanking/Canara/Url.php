<?php

namespace RZP\Gateway\Netbanking\Canara;

class Url
{
    const LIVE_DOMAIN   = 'https://netbanking.canarabank.in/entry';
    const TEST_DOMAIN   = 'https://testupgrade.canarabank.in/B001';

    const AUTHORIZE     = '/merchantretailencr';
    const VERIFY        = '/encrMerchVerify';
}
