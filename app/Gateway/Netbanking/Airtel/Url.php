<?php

namespace RZP\Gateway\Netbanking\Airtel;

class Url
{
    const LIVE_DOMAIN   = 'https://ecom.airtelmoney.in/ecom/v2';
    const TEST_DOMAIN   = 'https://sit.airtelmoney.in/ecom/v2';

    const AUTHORIZE     = '/initiatePayment';
    const VERIFY        = '/inquiry';
    const REFUND        = '/reversal';
}
