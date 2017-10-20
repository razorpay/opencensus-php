<?php

namespace RZP\Gateway\Upi\Sbi;

class Url
{
    const TEST_DOMAIN  = 'https://uatupi.onlinesbi.com/upi/web';

    const AUTHORIZE    = '/meCollectInitiateWeb';
    const VERIFY       = '/meTranStatusQueryWeb';
    const REFUND       = 'meRefund'; // TODO: This has not been shared yet
    const VALIDATE_VPA = '/validateVPAWeb';
}