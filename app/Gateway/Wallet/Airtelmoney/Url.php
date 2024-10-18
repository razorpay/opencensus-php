<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use RZP\Gateway\Netbanking\Airtel;

// Domain for test/live and routes for authorize, verify and refund are same as Netbanking/Airtel
// So extending Netbanking/Airtel/Url class
class Url extends Airtel\Url
{
    const LIVE_DOMAIN   = 'https://ecom.airtelbank.com/payment/ecom/v2';
    const TEST_DOMAIN   = 'https://apbuat.airtelbank.com/ecom/new/v2';
    const AUTHORIZE     = '/initiatePayment';
    const VERIFY        = '/inquiry';
    const REFUND        = '/reversal';
}
