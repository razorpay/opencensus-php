<?php

namespace RZP\Gateway\Netbanking\Bob;

class Url
{
    // TODO: Add live domain once we get it
    const LIVE_DOMAIN = '';
    const TEST_DOMAIN = 'http://14.140.233.72';

    const AUTHORIZE = '/2FABankAwayRetail/sgonHttpHandler.aspx?Action.PaymentIntegration.ShoppingMall.Login.Init5=Y';
    const VERIFY    = '/bobverify/RAZORPAY_Verify.ashx';
}
