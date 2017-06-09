<?php

namespace RZP\Gateway\Netbanking\Rbl;

class Url
{
    const LIVE_DOMAIN = 'https://online.rblbank.com/corp/';
    const TEST_DOMAIN = 'https://onlineuat.rblbank.com/corp/';

    const AUTHORIZE   = 'AuthenticationController?';
    const VERIFY      = 'XService?';
}
