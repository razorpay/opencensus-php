<?php

namespace Gateway\Sbiepay;

class Url
{
    const TEST_DOMAIN   = 'https://test.sbiepay.com/secure';
    const LIVE_DOMAIN   = '';

    const PAY           = '/AggregatorHostedListener';
    const QUERY         = '/AggMerchantStatusQueryAction';
    const REFUND        = '/AggregatorRefundRequest';
}