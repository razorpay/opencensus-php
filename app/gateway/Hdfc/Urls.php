<?php

namespace Gateway\Hdfc;

class Urls
{
    const TEST_ENROLL_URL = 'https://securepgtest.fssnet.co.in:443/pgway/servlet/MPIVerifyEnrollmentXMLServlet';

    const TEST_AUTH_NOT_ENROLLED_URL = 'https://securepgtest.fssnet.co.in:443/pgway/servlet/TranPortalXMLServlet';

    const TEST_AUTH_ENROLLED_URL = 'https://securepgtest.fssnet.co.in:443/pgway/servlet/MPIPayerAuthenticationXMLServlet';

    const TEST_SUPPORT_TXN_URL = 'https://securepgtest.fssnet.co.in:443/pgway/servlet/TranPortalXMLServlet';
}