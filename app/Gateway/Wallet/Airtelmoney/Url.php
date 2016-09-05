<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

class URL
{
    const LIVE_DOMAIN = 'https://ecom.airtelmoney.in/oneClick/';
    const TEST_DOMAIN = 'https://sit.airtelmoney.in/oneClick/';

    const AUTHORIZE = 'signIn?REQUEST=ECOMM_SIGNON';
    const REFUND = 'ECommRequest.action?REQUEST=ECOMM_REVERSAL';
    const VERIFY = 'ECommRequest.action?REQUEST=ECOMM_INQ';
}
