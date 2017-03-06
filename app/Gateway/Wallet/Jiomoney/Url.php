<?php

namespace RZP\Gateway\Wallet\Jiomoney;

class Url
{
    const TEST_DOMAIN                = 'https://testpg.rpay.co.in/reliance-webpay/';
    const LIVE_DOMAIN                = 'https://pp2pay.jiomoney.com/reliance-webpay/';

    const TEST_VERIFY_DOMAIN         = 'https://testbill.rpay.co.in:8443/';
    const LIVE_VERIFY_DOMAIN         = 'https://pp2bill.jiomoney.com:8443/';

    const AUTHORIZE                  = 'v1.0/jiopayments';
    const REFUND                     = 'jiorefund';
    const VERIFY                     = 'Services/TransactionInquiry';
    const PAYMENT_STATUS             = 'v1.0/payment/status';
}
