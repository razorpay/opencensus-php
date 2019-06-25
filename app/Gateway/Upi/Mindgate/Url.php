<?php

namespace RZP\Gateway\Upi\Mindgate;

class Url
{
    // TODO: Can /upi be moved to the end of this string
    const TEST_DOMAIN       = 'https://upitest.hdfcbank.com';
    const LIVE_DOMAIN       = 'https://upi.hdfcbank.com';

    const AUTHENTICATE      = '/upi/meTransCollectSvc';
    const VERIFY            = '/upi/transactionStatusQuery';
    const REFUND            = '/upi/refundReqSvc';

    const VALIDATE_VPA      = '/upi/checkMeVirtualAddress';

    const INTENT_TPV        = '/upi/mePayInetentReq';
}
