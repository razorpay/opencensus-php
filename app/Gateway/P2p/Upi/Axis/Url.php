<?php

namespace RZP\Gateway\P2p\Upi\Axis;

class Url
{
    const TEST_DOMAIN          = 'https://upiuatv3.axisbank.co.in';

    const LIVE_DOMAIN          = 'https://upisdk.axisbank.co.in';

    const DEREGISTER           = '/api/b2/merchants/customer/deregister';

    const VALIDATE_VPA         = '/api/b2/merchants/vpas/validity';

    const ADD_BANK_ACCOUNT     = '/api/b2/merchants/accounts/add';

    const DELETE_VPA           = '/api/b2/merchants/vpas/deleteVpa';

    const ADD_DEFAULT          = '/api/b2/merchants/vpas/addDefault';

    const RAISE_QUERY          = '/api/b2/merchants/transactions/query/raise';

    const QUERY_STATUS         = '/api/b2/merchants/transactions/query/status';

    const BLOCK_VPA            = '/api/b2/merchants/vpas/blockAndSpam';

    const UNBLOCK_VPA          = '/api/b2/merchants/vpas/unblock';

    const GET_BLOCKED          = '/api/b2/merchants/vpas/block/list';

    const RETRIEVE_BANKS       = '/api/b2/merchants/banks';
}
