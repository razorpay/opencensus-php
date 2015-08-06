<?php

namespace Gateway\Mobikwik;

class Url
{
    const LIVE_DOMAIN   = 'https://www.mobikwik.com';
    const TEST_DOMAIN   = 'https://test.mobikwik.com/mobikwik';

    const AUTHORIZE     = '/wallet';
    const REFUND        = '/walletrefund';
    const VERIFY        = '/checkstatus';
}