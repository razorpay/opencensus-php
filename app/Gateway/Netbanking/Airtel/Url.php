<?php

namespace RZP\Gateway\Netbanking\Airtel;

class Url
{
    const LIVE_DOMAIN   = 'https://ecom.airtelbank.com/payment/ecom/v2';
    const TEST_DOMAIN   = 'https://apbuat.airtelbank.com:5050/ecom/v2';

    const AUTHORIZE     = '/initiatePayment';
    const VERIFY        = '/inquiry';
    const REFUND        = '/reversal';
}
