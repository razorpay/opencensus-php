<?php

namespace RZP\Gateway\Upi\Hulk;

class Url
{
    const LIVE_DOMAIN          = 'https://upiswitch2.hdfcbank.com/';
    const TEST_DOMAIN          = 'https://upitestswitch2.hdfcbank.com/';

    const AUTHORIZE            = 'v1/p2p/create/direct';
    const VERIFY               = 'v1/merchants/p2p';

    const LIVE_MINDGATE_DOMAIN = 'https://upiv2.hdfcbank.com';
    const TEST_MINDGATE_DOMAIN = 'https://upitestv2.hdfcbank.com';

    const MG_OAUTH_TOKEN       = '/oauth/token';
    const MG_REFUND            = '/upi/meapi/meCashBackPayTranReqWeb';
    const MG_VERIFY_REFUND     = '/upi/meapi/upi_trn_status';
}
