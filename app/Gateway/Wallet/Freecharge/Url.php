<?php

namespace RZP\Gateway\Wallet\Freecharge;

use RZP\Exception;

class Url
{
    // For processing payments through freecharge
    const LIVE_DOMAIN = 'https://checkout.freecharge.in';
    const TEST_DOMAIN = 'https://checkout-sandbox.freecharge.in';
    const LOGIN       = 'login';

    // For Login and OAuth 2.0 exchanges
    const LIVE_LOGIN_DOMAIN = 'https://login.freecharge.in';
    const TEST_LOGIN_DOMAIN = 'https://login-sandbox.freecharge.in';

// --------------------- Checkout Endpoint Urls --------------------------------------------

    const INIT_TRANSACTION  = '/api/v1/co/pay/init';
    const FETCH_TRANSACTION = '/api/v1/co/transaction';
    const REFUND            = '/api/v1/co/refund';

    // Wallet API
    const GET_BALANCE       = '/api/v1/co/oauth/wallet/balance';
    const DEBIT_WALLET      = '/api/v1/co/oauth/wallet/debit';
    const TOPUP_REDIRECT    = '/api/v1/co/pay/init';

    // Verify API
    const VERIFY            = '/api/v1/co/transaction/status';

// --------------------- End Checkout Endpoint Urls --------------------------------------------

// --------------------- Login Endpoint Urls ---------------------------------------------------

    const OTP_GENERATE   = '/api/v2/co/oauth/user/generate/otp';
    const OTP_RESEND     = '/api/v2/co/oauth/user/resend/otp';
    const OTP_SUBMIT     = '/api/v2/co/oauth/user/login';
    const OTP_REDIRECT   = '/api/v1/co/oauth/user/register';
    const USER_DETAILS   = '/api/v1/co/oauth/user/details';
    const EXCHANGE_TOKEN = '/api/v1/co/oauth/exchange/token';

// --------------------- End Login Endpoint Urls -----------------------------------------------

    public static $CHECKOUT_DOMAIN_ENDPOINTS = [
        self::INIT_TRANSACTION,
        self::FETCH_TRANSACTION,
        self::REFUND_TRANSACTION,
        self::GET_BALANCE,
        self::DEBIT_WALLET,
        self::TOPUP_WALLET,
        self::VERIFY,
    ];

    public static $LOGIN_DOMAIN_ENDPOINTS = [
        self::EXCHANGE_TOKEN,
        self::OTP_GENERATE,
        self::OTP_REDIRECT,
        self::OTP_RESEND,
        self::OTP_SUBMIT,
        self::USER_DETAILS,
    ];
}
