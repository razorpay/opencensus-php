<?php

namespace RZP\Gateway\Wallet\Payzapp;

class Url
{
    const LIVE_DOMAIN           = 'www.wibmo.com';
    const TEST_DOMAIN           = 'wallet.pc.enstage-sas.com';

    const PAY                   = '/vpcpay';
    const AMA                   = '/vpcdps';
    const PICKUP_DATA           = '/v1/wPay/pickup';

    const ACOSA_TEST_DOMAIN     = 'pg.pc.enstage-sas.com';
    const ACOSA_LIVE_DOMAIN     = 'pg.wibmo.com';

    const REFUND                = '/AccosaPGAPI/DirectMerchantAPI';
    const VERIFY                = '/AccosaPG/PGServer';

}
