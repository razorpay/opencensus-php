<?php

namespace RZP\Gateway\Upi\Sbi;

class Url
{
    const TEST_DOMAIN  = 'https://uatupi.onlinesbi.com/upi/web';
    const LIVE_DOMAIN  = 'https://upi.onlinesbi.com/upi/web';

    const AUTHORIZE    = '/meCollectInitiateWeb';
    const VERIFY       = '/meTranStatusQueryWeb';
    const VALIDATE_VPA = '/validateVPAWeb';
}
