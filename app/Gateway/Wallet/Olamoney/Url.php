<?php

namespace RZP\Gateway\Wallet\Olamoney;

class Url
{
    const TEST_DOMAIN   = 'http://sandbox.olamoney.in';
    const LIVE_DOMAIN   = 'https://om.olacabs.com';

    const OTP_GENERATE  = '/olamoney/v1/debit?phone=:contact';
    const AUTHORIZE     = '/olamoney/webview/index.html';
    const DEBIT         = '/olamoney/v1/capture';
    const REFUND        = '/olamoney/v2/refund';
    const VERIFY        = '/olamoney/v2/query';
}
