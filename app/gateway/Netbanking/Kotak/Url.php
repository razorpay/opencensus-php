<?php

namespace Gateway\Netbanking\Kotak;

class Url
{
    const LIVE_DOMAIN   = 'https://netbanking.hdfcbank.com';
    const TEST_DOMAIN   = 'https://203.196.200.42';

    const AUTHORIZE     = '/pgx/ksecLogin.jsp';
    const VERIFY        = '/pg/kbsegquery.jsp';
}
