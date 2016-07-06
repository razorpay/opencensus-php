<?php

namespace Gateway\Hdfc;

class Urls
{
    const TEST_DOMAIN           = 'https://securepgtest.fssnet.co.in';
    const LIVE_DOMAIN           = 'https://securepg.fssnet.co.in';

    const ENROLL_URL            = '/pgway/servlet/MPIVerifyEnrollmentXMLServlet';
    const AUTH_NOT_ENROLLED_URL = '/pgway/servlet/TranPortalXMLServlet';
    const AUTH_ENROLLED_URL     = '/pgway/servlet/MPIPayerAuthenticationXMLServlet';
    const SUPPORT_PAYMENT_URL   = '/pgway/servlet/TranPortalXMLServlet';
}