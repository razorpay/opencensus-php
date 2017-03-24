<?php

namespace RZP\Gateway\Netbanking\Federal;

class Url
{
    const LIVE_DOMAIN   = 'https://www.fednetbank.com';
    const TEST_DOMAIN   = 'https://www.fednetbank.com';

    /**
     * Verify_Broken is used when Bank Payment Id
     * is not present In the DB
     * while making verify call
     */
    const AUTHORIZE     = '/corp/BANKAWAY';
    const VERIFY        = '/Verify';
}
