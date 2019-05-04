<?php

namespace RZP\Gateway\Netbanking\Federal;

class Url
{
    const LIVE_DOMAIN   = 'https://www.fednetbank.com';
    const TEST_DOMAIN   = 'http://14.142.52.59:8080';

    const AUTHORIZE     = '/corp/BANKAWAY';
    const VERIFY        = '/Verify';
    const VERIFY_TEST   = '/VerifyWeb/';
}
