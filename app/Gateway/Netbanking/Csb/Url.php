<?php

namespace RZP\Gateway\Netbanking\Csb;

class Url
{
    const TEST_DOMAIN = 'https://uat1.csb.co.in/newibanking';
    const LIVE_DOMAIN = 'https://www.csbnet.co.in';

    const AUTHORIZE   = '/BkPgCSBPayPGIntf.aspx';
    const VERIFY      = '/BkPgVrfyCSBPayPGIntf.aspx';
}
