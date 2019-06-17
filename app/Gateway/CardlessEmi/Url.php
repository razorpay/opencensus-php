<?php

namespace RZP\Gateway\CardlessEmi;

class Url
{
    // Early Salary Urls
    const TEST_DOMAIN_EARLYSALARY    = 'https://apps.socialworth.in';
    const LIVE_DOMAIN_EARLYSALARY    = 'https://api.socialworth.in/cardlessemi';

    const CHECK_ACCOUNT_EARLYSALARY  = '/checkaccount';
    const FETCH_TOKEN_EARLYSALARY    = '/customertoken';
    const AUTHORIZE_EARLYSALARY      = '/paymentauthorize';
    const CAPTURE_EARLYSALARY        = '/paymentcapture';
    const VERIFY_EARLYSALARY         = '/paymentverify';
    const REFUND_EARLYSALARY         = '/paymentrefund';

    // Zest Money Urls
    const TEST_DOMAIN_ZESTMONEY      = 'http://staging-app.zestmoney.in/PaymentGateway/Razorpay';
    const LIVE_DOMAIN_ZESTMONEY      = 'https://app.zestmoney.in/PaymentGateway/RazorPay';

    const CHECK_ACCOUNT_ZESTMONEY    = '/users/v1';
    const FETCH_TOKEN_ZESTMONEY      = '/tokens/v1';
    const AUTHORIZE_ZESTMONEY        = '/payments';
    const CAPTURE_ZESTMONEY          = '/payments/capture';
    const VERIFY_ZESTMONEY           = '/payments/verify';
    const REFUND_ZESTMONEY           = '/payments/refund';

    // Flex Money Urls
    const TEST_DOMAIN_FLEXMONEY      = 'https://staging.instacred.me/app';
    const LIVE_DOMAIN_FLEXMONEY      = 'https://instacred.me/app';

    const CHECK_ACCOUNT_FLEXMONEY    = '/users/check-account';
    const AUTHORIZE_FLEXMONEY        = '/payments/authorize';
    const CAPTURE_FLEXMONEY          = '/payments/capture';
    const VERIFY_FLEXMONEY           = '/payments/verify';
    const REFUND_FLEXMONEY           = '/payments/refund';
    const VERIFY_REFUND              = '/refunds/verify';

    //ePayLater Urls

    const TEST_DOMAIN_EPAYLATER     = 'https://hodor.epaylater.in:8095';
    const LIVE_DOMAIN_EPAYLATER     = ''; // TODO: fill this later.

    const CHECK_ACCOUNT_EPAYLATER   = '/user/v1/checkaccount';
    const FETCH_TOKEN_EPAYLATER     = '/user/v1/token';
    const AUTHORIZE_EPAYLATER       = '/payments';
    const CAPTURE_EPAYLATER         = '/payments/{id}/capture';
    const REFUND_EPAYLATER          = '/payments/{id}/refund';
    const VERIFY_EPAYLATER          = '/payments/{id}';
    const VERIFY_REFUND_EPAYLATER   = '/refunds/{id}';
}
