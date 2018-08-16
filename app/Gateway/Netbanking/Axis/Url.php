<?php

namespace RZP\Gateway\Netbanking\Axis;

class Url
{
        const RETAIL_AUTHORIZE_LIVE_DOMAIN  = 'https://retail.axisbank.co.in/';

        const RETAIL_VERIFY_LIVE_DOMAIN     = 'https://www.axisbiconnect.co.in/';

        const RETAIL_AUTHORIZE_TEST_DOMAIN  = 'https://febauat.axisbank.co.in/';

        const RETAIL_VERIFY_TEST_DOMAIN     = 'https://mtp.axisbank.co.in/';

        const CORPORATE_AUTHORIZE_DOMAIN = 'https://corporate.axisbank.co.in/';

        const CORPORATE_VERIFY_DOMAIN    = 'https://www.axisbiconnect.co.in/';

        const AUTHORIZE_CORPORATE        = 'wps/portal/cBanking/AxisSMCorporateLogin/axissmcorppage';

        const VERIFY_CORPORATE           = 'AXISPaymentsVerification/Web/Applications/CorpQuery.aspx';

        const EMANDATE_TEST_DOMAIN       = 'https://uat-etendering.axisbank.co.in/easypay2.0/frontend/api';

        const EMANDATE_LIVE_DOMAIN       = 'https://easypay.axisbank.co.in/index.php/api';

        const AUTHORIZE_RETAIL           = 'wps/portal/rBanking/AxisSMRetailLogin/axissmretailpage';

        const VERIFY_RETAIL              = 'axispaymentsverification/web/applications/QueryEnc.aspx';

        const AUTHORIZE_EMANDATE         = '/payment';

        const VERIFY_EMANDATE            = '/enquiry';
}
