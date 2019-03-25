<?php

namespace RZP\Gateway\Netbanking\Kotak;

class Url
{
    const LIVE_DOMAIN        = 'https://www.kotak.com';
//    const TEST_DOMAIN      = 'https://203.196.200.42';
    const TEST_DOMAIN        = 'https://netbank.uat.kotak.com';

    const TEST_AUTHORIZE     = '/pgx/ksecLogin.jsp';
    const LIVE_AUTHORIZE     = '/pmtgt/ksecLogin.jsp';

    const LIVE_API_GW_DOMAIN = 'https://apigw.kotak.com:8444';
    const TEST_API_GW_DOMAIN = 'https://apigwuat.kotak.com:8443';

    const VERIFY             = '/KBSecPG';
    const TOKEN              = '/k2/auth/oauth/v2/token';
}
