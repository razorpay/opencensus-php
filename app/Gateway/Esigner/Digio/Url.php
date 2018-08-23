<?php

namespace RZP\Gateway\Esigner\Digio;

class Url
{
    const LIVE_DOMAIN = 'https://api.digio.in';
    const TEST_DOMAIN = 'https://ext.digio.in:444';

    const REDIRECT_LIVE_DOMAIN = 'https://app.digio.in';
    const REDIRECT_TEST_DOMAIN = 'https://ext.digio.in';

    const AUTHORIZE = '/#/gateway/login/{id}/{txnId}/{contact}';
    const CREATE = '/v2/client/enach/mandate/create_form';
    const FETCH  = '/v2/client/enach/mandate/download';
    const VERIFY = '/v2/client/enach/mandate/form/{id}';
}
