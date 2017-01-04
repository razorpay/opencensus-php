<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
    const LIVE_AUTHORIZE_DOMAIN = 'https://retail.axisbank.co.in/';
    const TEST_AUTHORIZE_DOMAIN = 'https://retail.axisbank.co.in/';

    const LIVE_VERIFY_DOMAIN = 'https://www.axisbank.co.in/';
    const TEST_VERIFY_DOMAIN = 'https://www.axisbank.co.in/';

    const AUTHORIZE = 'wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?';

    const VERIFY      = 'Verification/Web/Applications/Query.aspx';
}
