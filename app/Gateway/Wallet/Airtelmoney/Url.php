<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

class URL
{
    const LIVE_DOMAIN = 'https://ecom.airtelmoney.in/oneClick';
    const TEST_DOMAIN = 'https://ecom.airtelmoney.in/oneClick';

    const DEBIT_WALLET = 'signIn?REQUEST=ECOMM_SIGNON';
    const REFUND = 'ECommRequest.action?REQUEST=ECOMM_REVERSAL';
    const VERIFY = 'ECommRequest.action?REQUEST=ECOMM_INQ';
}
