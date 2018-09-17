<?php

namespace RZP\Gateway\Esigner\Legaldesk;

class Url
{
    // Todo: update here once we get live api details
    const LIVE_DOMAIN = 'https://api.signdesk.in/api/live';
    const TEST_DOMAIN = 'https://signdesk.in:6066/api/sandbox';

    const CREATE = '/emandateRequest';
    const FETCH  = '/getEmandateSignedXml';
}
