<?php

namespace Gateway\Mobikwik;

class Url
{
    const LIVE_DOMAIN 	= 'https://mobikwik.com';
    const TEST_DOMAIN   = 'https://mobikwik.com';
    // const TEST_DOMAIN   = 'https://test.mobikwik.com/mobikwik';
    const API_DOMAIN    = 'https://walletapi.mobikwik.com/querywallet';

    const AUTHORIZE 	= '/wallet';
    const REFUND 		= '/walletrefund';
    const VERIFY 		= '/checkstatus';
    const CHECK_USER 	= '/querywallet';
    const OTP_GENERATE 	= '/otpgenerate';
    const OTP_SUBMIT 	= '/debitwallet';
}