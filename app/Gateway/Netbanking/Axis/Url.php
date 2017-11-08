<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
    const RETAIL_AUTHORIZE_DOMAIN = 'https://retail.axisbank.co.in/';

    const RETAIL_VERIFY_DOMAIN    = 'https://www.axisbiconnect.co.in/';

    const EMANDATE_TEST_DOMAIN    = 'https://uat-etendering.axisbank.co.in/index.php/api';

    // TODO: Change to live URL
    const EMANDATE_LIVE_DOMAIN    = 'https://uat-etendering.axisbank.co.in/index.php/api';

    const AUTHORIZE_RETAIL        = 'wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage?AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2&CATEGORY_ID=IRCSM';

    const VERIFY_RETAIL           = 'AXISPaymentsVerification/Web/Applications/Query.aspx';

    const AUTHORIZE_EMANDATE      = '/payment';

    const VERIFY_EMANDATE         = '/enquiry';
}
