<?php

namespace RZP\Gateway\Netbanking\Sbi;

class Url
{
    const TEST_DOMAIN = 'https://uatmerchant.onlinesbi.com';
    const LIVE_DOMAIN = 'https://merchant.onlinesbi.com';

    const AUTHORIZE_TEST           = '/merchantntrp/merchantprelogin.htm';
    const VERIFY_TEST              = '/thirdparties/doubleverification.htm';
    const VERIFY_MANDATE_TEST      = '/thirdpartiesdv/doubleverification.htm';

    const AUTHORIZE_LIVE           = '/merchantcug3/merchantprelogin.htm';
    const VERIFY_LIVE              = '/thirdpartiescug/doubleverification.htm';
    //TODO
    const VERIFY_MANDATE_LIVE      = '';
}
