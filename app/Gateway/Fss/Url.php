<?php

namespace RZP\Gateway\Fss;

class Url
{
    const TEST_DOMAIN = 'test_domain';

    const LIVE_DOMAIN = 'live_domain';

    public static $urlMap = [
        Acquirer::FSS => [
            self::TEST_DOMAIN => "https://merchanthubtest.fssnet.co.in/",
            self::LIVE_DOMAIN => "",

            Action::PURCHASE  => "PGAggregator/MerchaggrPayment.htm?param=paymentInit&",
            Action::REFUND    => "PGAggregator/MerchaggrPayment.htm?param=supportInit",
            Action::VERIFY    => "PGAggregator/MerchaggrPayment.htm?param=supportInit",
        ],
        Acquirer::BOB   => [
            self::TEST_DOMAIN => "https://ipg.bobgateway.com/IPG",
            self::LIVE_DOMAIN => "https://ipg.bobgateway.com/IPG",

            Action::PURCHASE => "/VPAS.htm?actionVPAS=VbvVEReqProcessHTTP&",
            Action::REFUND    => "/tranPipe.htm?param=tranInit",
            Action::VERIFY    => "/tranPipe.htm?param=tranInit",
        ]
    ];
}
