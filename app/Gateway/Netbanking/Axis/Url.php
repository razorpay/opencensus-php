<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
        const RETAIL_AUTHORIZE_DOMAIN    = 'https://retail.axisbank.co.in/';

        const RETAIL_VERIFY_DOMAIN       = 'https://www.axisbiconnect.co.in/';

        const CORPORATE_AUTHORIZE_DOMAIN = 'https://corporate.axisbank.co.in/';

        const AUTHORIZE_CORPORATE        = 'wps/portal/cBanking/AxisSMCorporateLogin/axissmcorppage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';

        const EMANDATE_TEST_DOMAIN       = 'https://uat-etendering.axisbank.co.in/index.php/api';

        const EMANDATE_LIVE_DOMAIN       = 'https://easypay.axisbank.co.in/index.php/api';

        const AUTHORIZE_RETAIL           = 'wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';

        const VERIFY_RETAIL              = 'AXISPaymentsVerification/Web/Applications/Query.aspx';

        const AUTHORIZE_EMANDATE         = '/payment';

        const VERIFY_EMANDATE            = '/enquiry';
}
