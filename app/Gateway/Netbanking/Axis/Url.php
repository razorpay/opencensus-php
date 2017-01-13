<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
    const AUTHORIZE_DOMAIN = 'https://retail.axisbank.co.in/';

    const VERIFY_DOMAIN    = 'https://www.axisbiconnect.co.in/';

    const AUTHORIZE        = 'wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';

    const VERIFY           = 'AXISPaymentsVerification/Web/Applications/Query.aspx';
}
