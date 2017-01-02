<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
    const LIVE_DOMAIN = 'https://';
    const TEST_DOMAIN = 'https://';

    const AUTHORIZE   = 'retail.axisbank.co.in/wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';

    const VERIFY      = 'www.axisbank.co.in/Verification/Web/Applications/Query.aspx';
}
