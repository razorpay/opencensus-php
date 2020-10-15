<?php

namespace RZP\Gateway\Netbanking\Sbi;

class Url
{
    const TEST_DOMAIN = 'https://uatmerchant.onlinesbi.com';
    const LIVE_DOMAIN = 'https://merchant.onlinesbi.sbi';

    const AUTHORIZE_TEST           = '/merchantntrp/merchantprelogin.htm';
    const VERIFY_TEST              = '/thirdparties/doubleverification.htm';
    const AUTHORIZE_MANDATE_TEST   = '/npcimandatecug/merchantprelogin.htm';
    const VERIFY_MANDATE_TEST      = '/thirdpartiesdv/doubleverification.htm';

    const AUTHORIZE_LIVE           = '/merchant/merchantprelogin.htm';
    const VERIFY_LIVE              = '/thirdparties/doubleverification.htm';
    const AUTHORIZE_MANDATE_LIVE   = '/npcimandate/merchantprelogin.htm';
    const VERIFY_MANDATE_LIVE      = '/thirdparties/doubleverification.htm';
}
