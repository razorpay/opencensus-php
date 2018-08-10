<?php

namespace RZP\Gateway\Esigner\Legaldesk;

class Url
{
    // Todo: update here once we get live api details
    const LIVE_DOMAIN = '';
    const TEST_DOMAIN = 'https://signdesk.in:6066';

    const CREATE = '/api/sandbox/emandateRequest';
    const FETCH  = '/api/sandbox/getEmandateSignedXml';
}
