<?php

namespace RZP\Gateway\Upi\Hdfc;

class Url
{
    const TEST_DOMAIN       = '';
    const LIVE_DOMAIN       = 'https://upitest.hdfcbank.com';

    // TODO: placeholders for now
    const AUTHORIZE    = '/authorize';
    const VERIFY       = '/status';
    const REFUND       = '/refund';
}
