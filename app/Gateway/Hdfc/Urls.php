<?php

namespace RZP\Gateway\Hdfc;

class Urls
{
    const TEST_DOMAIN                    = 'https://securepgtest.fssnet.co.in/pgway';
    const LIVE_DOMAIN                    = 'https://securepg.fssnet.co.in/pgway';
    const TEST_DOMAIN_V2                 = 'https://securepgtest.fssnet.co.in/ipayb';
    const LIVE_DOMAIN_V2                 = 'https://securepayments.fssnet.co.in/hdfcbank';

    const TEST_DOMAIN_V3                 = 'https://securepaymentstest.hdfcbank.com/PG';
    const LIVE_DOMAIN_V3                 = 'https://hdfcbankpayments.hdfcbank.com/PG';

    const DEBIT_PIN_AUTHENTICATION_URL   = '/servlet/TranPortalXMLServlet';

    const ENROLL_URL                     = '/servlet/MPIVerifyEnrollmentXMLServlet';
    const AUTH_NOT_ENROLLED_URL          = '/servlet/TranPortalXMLServlet';
    const AUTH_ENROLLED_URL              = '/servlet/MPIPayerAuthenticationXMLServlet';
    const SUPPORT_PAYMENT_URL            = '/servlet/TranPortalXMLServlet';

    const PRE_AUTH_URL                   = '/servlet/PreAuthenticationXMLServlet';
}
