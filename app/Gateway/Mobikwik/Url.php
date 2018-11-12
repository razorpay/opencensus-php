<?php

namespace RZP\Gateway\Mobikwik;

class Url
{
    const LIVE_DOMAIN   = 'https://walletapi.mobikwik.com';
    // const TEST_DOMAIN   = 'https://mobikwik.com';
    const TEST_DOMAIN   = 'https://test.mobikwik.com';
    const API_DOMAIN    = 'https://walletapi.mobikwik.com';

    const AUTHORIZE     = '/wallet';
    const REFUND        = '/walletrefund';
    const VERIFY        = '/checkstatus';
    const CHECK_USER    = '/querywallet';
    const OTP_GENERATE  = '/otpgenerate';
    const OTP_SUBMIT    = '/debitwallet';
    const CREATE_USER   = '/createwalletuser';
}
