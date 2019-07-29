<?php

namespace RZP\Gateway\Upi\Sbi;

class Url
{
    const TEST_DOMAIN  = 'https://uatupi.onlinesbi.com';
    const LIVE_DOMAIN  = 'https://upi.onlinesbi.com';

    const AUTHORIZE    = '/upi/web/meCollectInitiateWeb';
    const VERIFY       = '/upi/web/meTranStatusQueryWeb';
    const VALIDATE_VPA = '/upi/web/validateVPAWeb';

    const OAUTH_TOKEN    = '/oauth/token';
}
