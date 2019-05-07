<?php

namespace RZP\Gateway\P2p\Upi\Axis;

class Url
{
    const TEST_DOMAIN          = 'https://upiuatv3.axisbank.co.in';

    const DEREGISTER           = '/api/b2/merchants/customer/deregister';

    const VALIDATE_VPA         = '/api/b2/merchants/vpas/validity';

    const RAISE_QUERY          = '/api/b2/merchants/transactions/query/raise';

    const QUERY_STATUS         = '/api/b2/merchants/transactions/query/status';
}
