<?php

namespace RZP\Gateway\Wallet\Freecharge;

use RZP\Exception;

class Url
{
    // For processing payments through freecharge
    const LIVE_CHECKOUT_DOMAIN           = 'https://checkout.freecharge.in';
    const TEST_CHECKOUT_DOMAIN           = 'https://checkout-sandbox.freecharge.in';

    // For Login and OAuth 2.0 exchanges
    const LIVE_LOGIN_DOMAIN              = 'https://login.freecharge.in';
    const TEST_LOGIN_DOMAIN              = 'https://login-sandbox.freecharge.in';

// --------------------- Checkout Endpoint Urls --------------------------------------------

    const INIT_TRANSACTION               = 'api/v1/co/pay/init';
    const FETCH_TRANSACTION              = 'api/v1/co/transaction';
    const REFUND_TRANSACTION             = 'api/v1/co/refund';

    // Wallet API
    const GET_BALANCE                    = 'api/v1/co/oauth/wallet/balance';
    const DEBIT_WALLET                   = 'api/v1/co/oauth/wallet/debit';
    const TOPUP_WALLET                   = 'api/v1/co/oauth/wallet/add';

    // Settlement API
    const TRANSACTION_SETTLEMENT         = 'api/v1/co/merchant/settled/transaction';

// --------------------- End Checkout Endpoint Urls --------------------------------------------

// --------------------- Login Endpoint Urls ---------------------------------------------------

    const OTP_GENERATE                   = 'api/v2/co/oauth/user/generate/otp';
    const RESEND_OTP                     = 'api/v2/co/oauth/user/resend/otp';
    const OTP_SUBMIT                     = 'api/v2/co/oauth/user/login';
    const USER_DETAILS                   = 'api/v1/co/oauth/user/details';

// --------------------- End Login Endpoint Urls -----------------------------------------------

    public static $CHECKOUT_DOMAIN_ENDPOINTS = [
        self::INIT_TRANSACTION,
        self::FETCH_TRANSACTION,
        self::REFUND_TRANSACTION,
        self::GET_BALANCE,
        self::DEBIT_WALLET,
        self::TOPUP_WALLET,
        self::TRANSACTION_SETTLEMENT,
    ];

    public static $LOGIN_DOMAIN_ENDPOINTS = [
        self::OTP_GENERATE,
        self::RESEND_OTP,
        self::OTP_SUBMIT,
        self::USER_DETAILS,
    ];
}
