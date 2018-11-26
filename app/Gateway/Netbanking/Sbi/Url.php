<?php

namespace RZP\Gateway\Netbanking\Sbi;

class Url
{
    const TEST_DOMAIN = 'https://uatmerchant.onlinesbi.com';
    const LIVE_DOMAIN = 'https://merchant.onlinesbi.com';

    const AUTHORIZE   = '/merchantreg/merchantprelogin.htm';
    const VERIFY      = '/thirdparties/doubleverification.htm';
}
