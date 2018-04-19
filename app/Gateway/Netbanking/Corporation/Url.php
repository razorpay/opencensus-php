<?php

namespace RZP\Gateway\Netbanking\Corporation;

class Url
{
    // TODO: Add live domain later
    const LIVE_DOMAIN   = '';
    const TEST_DOMAIN   = 'http://172.22.2.11:7003';

    const AUTHORIZE     = '/corp/OLT';
    const VERIFY        = '/corp/OLTDVER';
}
