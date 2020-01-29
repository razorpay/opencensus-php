<?php

namespace RZP\Gateway\Netbanking\Equitas;

class Url
{
    const LIVE_DOMAIN   = 'https://inet.equitasbank.com';
    const TEST_DOMAIN   = 'https://eqibt.equitasbank.com';

    const AUTHORIZE     = '/EquitasPaymentGateway/';
    const VERIFY        = '/EquitasPaymentGatewayInquiry/PaymentInquiry';
}
