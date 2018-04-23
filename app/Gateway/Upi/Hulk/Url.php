<?php

namespace RZP\Gateway\Upi\Hulk;

class Url
{
    const LIVE_DOMAIN  = 'https://upiswitch2.hdfcbank.com/';
    const TEST_DOMAIN  = 'https://upitestswitch2.hdfcbank.com/';

    //
    // @todo: Fix the URi once it's completed. It has to be on merchant proxy auth
    // along with the payment verify route
    //
    const AUTHORIZE    = 'v1/p2p/direct';
    const VERIFY       = 'v1/merchants/p2p';
}
