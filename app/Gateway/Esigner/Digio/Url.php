<?php

namespace RZP\Gateway\Esigner\Digio;

class Url
{
    const LIVE_DOMAIN = 'https://api.digio.in';
    const TEST_DOMAIN = 'https://ext.digio.in:444';

    const CREATE = '/v2/client/enach/mandate/create_form';
    const FETCH  = '/v2/client/enach/mandate/download';
    const REFUND = 'ECommRequest.action?REQUEST=ECOMM_REVERSAL';
    const VERIFY = 'ECommRequest.action?REQUEST=ECOMM_INQ';
}
