<?php

namespace RZP\Gateway\Upi\Hulk;

class Url
{
    const LIVE_DOMAIN  = 'https://upiswitch2.hdfcbank.com/';
    const TEST_DOMAIN  = 'https://upitestswitch2.hdfcbank.com/';

    const AUTHORIZE    = 'v1/p2p/create/direct';
    const VERIFY       = 'v1/merchants/p2p';
}
