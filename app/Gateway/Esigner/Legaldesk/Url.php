<?php

namespace RZP\Gateway\Esigner\Legaldesk;

class Url
{
    const LIVE_DOMAIN = 'https://api.signdesk.in/api/live';
    const LIVE_DOMAIN_V2 = 'https://in-enach.signdesk.com/api/live';
    const TEST_DOMAIN = 'https://uat.signdesk.in/api/sandbox';

    const CREATE = '/emandateRequest';
    const FETCH  = '/getEmandateSignedXml';
}
