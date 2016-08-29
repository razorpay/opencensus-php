<?php

namespace RZP\Gateway\Wallet\Olamoney;

class Url
{
    const TEST_DOMAIN   = 'http://sandbox.olamoney.in';
    const LIVE_DOMAIN   = 'https://om.olacabs.com';

    const OTP_GENERATE  = '/olamoney/v1/userWallet/addUserWallet';
    const OTP_SUBMIT    = '/olamoney/v1/userWallet/linkUserWallet';
    const GET_BALANCE   = '/olamoney/v1/userBalance';
    const TOPUP_WALLET  = '/olamoney/webview/index.html';
    const TOPUP_REDIRECT    = '/olamoney/v1/verifyloadmoney';
    const AUTHORIZE     = '/olamoney/webview/index.html';
    const DEBIT_WALLET  = '/olamoney/v1/autoDebit';
    const REFUND        = '/olamoney/v2/refund';
    const VERIFY        = '/olamoney/v2/query';
}
