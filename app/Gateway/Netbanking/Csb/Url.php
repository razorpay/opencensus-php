<?php

namespace RZP\Gateway\Netbanking\Csb;

class Url
{
    const TEST_DOMAIN = 'http://203.197.151.38/newibanking';
    const LIVE_DOMAIN = 'https://www.csbnet.co.in';

    const AUTHORIZE   = '/BkPgEmVantageIntf.aspx';
    const VERIFY      = '/BkPgVrfyEmVantageIntf.aspx';
}
