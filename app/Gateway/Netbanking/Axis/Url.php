<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
    // hacky -- I'm sure there's a better way to do this
    const LIVE_DOMAIN = 'https://';
    const TEST_DOMAIN = 'https://';

    const AUTHORIZE   = 'retail.axisbank.co.in/wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?';
    const VERIFY      = 'www.axisbank.co.in/Verification/Web/Applications/Query.aspx';
}
