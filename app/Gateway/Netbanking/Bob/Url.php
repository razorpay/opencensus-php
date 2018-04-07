<?php

namespace RZP\Gateway\Netbanking\Bob;

class Url
{
    const LIVE_DOMAIN = 'https://www.bobibanking.com';
    const TEST_DOMAIN = 'http://14.140.233.72';

    const AUTHORIZE      = '/2FABankAwayRetail/sgonHttpHandler.aspx?Action.PaymentIntegration.ShoppingMall.Login.Init5=Y';
    const AUTHORIZE_LIVE = '/BankAwayRetail/sgonHttpHandler.aspx?Action.PaymentIntegration.ShoppingMall.Login.Init5=Y';

    const VERIFY    = '/bobverify/RAZORPAY_Verify.ashx';
}
