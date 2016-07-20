<?php

namespace RZP\Gateway\Ebs;

class Url
{
    const TEST_DOMAIN           = 'https://secure.ebs.in/pg/ma';
    const LIVE_DOMAIN           = 'https://secure.ebs.in/pg/ma';
    const LIVE_API_DOMAIN       = 'https://api.secure.ebs.in/api/1_0';
    const TEST_API_DOMAIN       = 'https://api.secure.ebs.in/api/1_0';

    const AUTHORIZE             = '/payment/request';
    const CAPTURE               = 'Capture';
    const CANCEL                = 'Cancel';
    const REFUND                = 'Refund';
    const STATUS                = 'Status';
    const STATUS_BY_REF         = 'statusByRef';
    const GET_CURRENCY_VALUE    = 'getCurrencyValue';
}
