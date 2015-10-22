<?php

namespace Gateway\Sbiepay;

class Url
{
    const TEST_DOMAIN   = 'https://test.sbiepay.com/secure';
    const LIVE_DOMAIN   = '';

    const AUTHORIZE     = '/MerchantHostedListener';
    const VERIFY        = '/AggMerchantStatusQueryAction';
    const REFUND        = '/AggregatorRefundRequest';
}