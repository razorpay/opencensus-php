<?php

namespace Gateway\Wallet\Payumoney;

class Url
{
    const TEST_BASE_URL = "http://sandbox.olamoney.in"
    const LIVE_BASE_URL = "https://om.olacabs.com";

    const OTP_GENERATE      = '/olamoney/v1/debit';
    const DEBIT             = '/olamoney/v1/capture';
}
