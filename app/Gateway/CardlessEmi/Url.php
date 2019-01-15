<?php

namespace RZP\Gateway\CardlessEmi;

class Url
{
    const TEST_DOMAIN_EARLYSALARY    = 'https://apps.socialworth.in';
    const LIVE_DOMAIN_EARLYSALARY    = 'https://api.socialworth.in/cardlessemi';

    const CHECK_ACCOUNT_EARLYSALARY  = '/checkaccount';
    const FETCH_TOKEN_EARLYSALARY    = '/customertoken';
    const AUTHORIZE_EARLYSALARY      = '/paymentauthorize';
    const CAPTURE_EARLYSALARY        = '/paymentcapture';
    const VERIFY_EARLYSALARY         = '/paymentverify';
    const REFUND_EARLYSALARY         = '/paymentrefund';

    const TEST_DOMAIN_ZESTMONEY      = 'http://staging-app.zestmoney.in/PaymentGateway/Razorpay';
    const LIVE_DOMAIN_ZESTMONEY      = 'https://app.zestmoney.in/PaymentGateway/RazorPay';

    const CHECK_ACCOUNT_ZESTMONEY    = '/users/v1';
    const FETCH_TOKEN_ZESTMONEY      = '/tokens/v1';
    const AUTHORIZE_ZESTMONEY        = '/payments';
    const CAPTURE_ZESTMONEY          = '/payments/capture';
    const VERIFY_ZESTMONEY           = '/payments/verify';
    const REFUND_ZESTMONEY           = '/payments/refund';
}
