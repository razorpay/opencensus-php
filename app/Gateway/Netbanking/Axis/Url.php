<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
    const RETAIL_AUTHORIZE_TEST_DOMAIN    = 'https://retail.axisbank.co.in/';
    const RETAIL_VERIFY_TEST_DOMAIN       = 'https://www.axisbiconnect.co.in/';

    const RETAIL_AUTHORIZE_LIVE_DOMAIN    = 'https://retail.axisbank.co.in/';
    const RETAIL_VERIFY_LIVE_DOMAIN       = 'https://www.axisbiconnect.co.in/';

    const RETAIL_AUTHORIZE                = 'wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';

    const RETAIL_VERIFY                   = 'AXISPaymentsVerification/Web/Applications/Query.aspx';

    // Verify is unavailable for corporate payments
    const CORPORATE_AUTHORIZE_TEST_DOMAIN = 'https://febauat.axisbank.co.in/';

    const CORPORATE_AUTHORIZE             = 'wps/portal/cBanking/AxisSMCorporateLogin/axissmcorppage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';
}
