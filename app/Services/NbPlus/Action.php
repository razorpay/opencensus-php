<?php

namespace RZP\Services\NbPlus;

class Action
{
    const AUTHORIZE              = 'authorize';
    const CALLBACK               = 'callback';
    const VERIFY                 = 'verify';
    const DEBIT                  = 'debit';
    const AUTHORIZE_FAILED       = 'authorize_failed';
    const PREPROCESS_CALLBACK    = 'preprocess_callback';
    const FORCE_AUTHORIZE_FAILED = 'force_authorize_failed';

    const CHECK_ACCOUNT          = 'check_account';
    const CAPTURE                = 'capture';
    const OTP_GENERATE           = 'otp_generate';
    const CALLBACK_OTP_SUBMIT    = 'callback_otp_submit';
    const OTP_RESEND             = 'otp_resend';
    const TOPUP                  = 'topup';
    const INTENT                 = 'intent';

    const SUPPORTED_ACTIONS = [
        self::AUTHORIZE,
        self::CALLBACK,
        self::VERIFY,
        self::AUTHORIZE_FAILED,
        self::DEBIT,
        self::PREPROCESS_CALLBACK,
        self::FORCE_AUTHORIZE_FAILED,
        self::CHECK_ACCOUNT,
        self::CAPTURE,
        self::OTP_GENERATE,
        self::OTP_RESEND,
        self::CALLBACK_OTP_SUBMIT,
        self::TOPUP,
    ];

    const PAYMENTS_SUPPORTED_ACTIONS = [
        self::FORCE_AUTHORIZE_FAILED,
    ];
}
