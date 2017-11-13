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

    
    const RETAIL_AUTHORIZE_DOMAIN = 'https://retail.axisbank.co.in/';

    const RETAIL_VERIFY_DOMAIN    = 'https://www.axisbiconnect.co.in/';

        // Verify is unavailable for corporate payments
    const CORPORATE_AUTHORIZE_TEST_DOMAIN = 'https://febauat.axisbank.co.in/';

    const CORPORATE_AUTHORIZE             = 'wps/portal/cBanking/AxisSMCorporateLogin/axissmcorppage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';


    const EMANDATE_TEST_DOMAIN    = 'https://uat-etendering.axisbank.co.in/index.php/api';

    const EMANDATE_LIVE_DOMAIN    = 'https://easypay.axisbank.co.in/index.php/api';

    const AUTHORIZE_RETAIL        = 'wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';

    const VERIFY_RETAIL           = 'AXISPaymentsVerification/Web/Applications/Query.aspx';

    const AUTHORIZE_EMANDATE      = '/payment';

    const VERIFY_EMANDATE         = '/enquiry';
}
