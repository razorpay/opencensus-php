<?php

namespace RZP\Gateway\Base;

class Action
{
    const PURCHASE               = 'purchase';
    const ADVICE                 = 'advice';
    const AUTHENTICATE           = 'authenticate';
    const AUTHORIZE              = 'authorize';
    const CAPTURE                = 'capture';
    const REFUND                 = 'refund';
    const VOID                   = 'void';
    const VERIFY                 = 'verify';
    const CALLBACK               = 'callback';
    const REVERSE                = 'reverse';
    const OTP_RESEND             = 'otp_resend';
    const OTP_GENERATE           = 'otp_generate';
    const VALIDATE_VPA           = 'validate_vpa';
    const VALIDATE_PUSH          = 'validate_push';
    const PAYOUT                 = 'payout';
    const PAYOUT_VERIFY          = 'payout_verify';
    const VERIFY_REFUND          = 'verify_refund';
    const OMNI_PAY               = 'omni_pay';
    const CREATE_TERMINAL        = 'create_terminal';
    const VERIFY_TERMINAL        = 'verify_terminal';
    const DEBIT                  = 'debit';

    public static $nonVerifiableActions = [
        self::AUTHENTICATE
    ];

    public static $cpsSupportedActions = [
        self::AUTHORIZE,
        self::CALLBACK,
        self::CAPTURE,
        self::VERIFY,
    ];

    public static $cardPaymentsSupportedActions = [
        self::AUTHORIZE,
        self::CALLBACK,
        self::CAPTURE,
        self::VERIFY,
        self::DEBIT,
    ];
}
