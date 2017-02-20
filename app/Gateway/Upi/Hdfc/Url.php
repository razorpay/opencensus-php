<?php

namespace RZP\Gateway\Upi\Hdfc;

class Url
{
    const TEST_DOMAIN       = 'https://upitest.hdfcbank.com';
    const LIVE_DOMAIN       = 'https://upitest.hdfcbank.com';

    const AUTHORIZE         = '/upi/meTransCollectSvc';
    const VERIFY            = '/upi/transactionStatusQuery';
    const REFUND            = '/upi/refundReqSvc';

    const VALIDATE_VPA      = '/upi/checkMeVirtualAddress';
}
