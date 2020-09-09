<?php

namespace RZP\Gateway\Netbanking\Pnb;

class Url
{

    const LIVE_DOMAIN = 'https://gateway.netpnb.com/Razorpay/';
    const TEST_DOMAIN = 'https://uatepay.netpnb.com/RazorPayTest/';

    const AUTHORIZE   = 'request.aspx';
    const VERIFY      = 'verification.aspx';
}
