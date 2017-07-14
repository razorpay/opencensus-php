<?php

namespace RZP\Gateway\Netbanking\Pnb;

class Url
{
    const LIVE_DOMAIN = '';
    const TEST_DOMAIN = 'https://125.18.17.50:2020/RazorPayTest/';

    // TODO : set verify url
    const AUTHORIZE   = 'Request.aspx';
    const VERIFY      = 'verification.aspx';
}
