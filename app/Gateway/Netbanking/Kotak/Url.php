<?php

namespace Gateway\Netbanking\Kotak;

class Url
{
    const LIVE_DOMAIN    = 'https://www.kotak.com';
    const TEST_DOMAIN    = 'https://203.196.200.42';

    const TEST_AUTHORIZE = '/pgx/ksecLogin.jsp';
    const LIVE_AUTHORIZE = '/pg/ksecLogin.jsp';
    const VERIFY         = '/pg/kbsegquery.jsp';
}
