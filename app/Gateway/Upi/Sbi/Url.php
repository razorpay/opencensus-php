<?php

namespace RZP\Gateway\Upi\Sbi;

class Url
{
    const TEST_DOMAIN  = 'https://uatupi.onlinesbi.com/upi/web';

    // TODO: Add live domain when we get it
    const LIVE_DOMAIN  = 'https://liveupi.onlinesbi.com/upi/web';

    const AUTHORIZE    = '/meCollectInitiateWeb';
    const VERIFY       = '/meTranStatusQueryWeb';
    const VALIDATE_VPA = '/validateVPAWeb';
}
