<?php

namespace RZP\Gateway\Base;

class Action
{
    const PURCHASE         = 'purchase';
    const ADVICE           = 'advice';
    const AUTHENTICATE     = 'authenticate';
    const AUTHORIZE        = 'authorize';
    const CAPTURE          = 'capture';
    const REFUND           = 'refund';
    const VOID             = 'void';
    const VERIFY           = 'verify';
    const CALLBACK         = 'callback';
    const PAY              = 'pay';
    const REVERSE          = 'reverse';
    const OTP_RESEND       = 'otp_resend';
    const OTP_GENERATE     = 'otp_generate';
    const VALIDATE_VPA     = 'validate_vpa';
    const VALIDATE_PUSH    = 'validate_push';
    const PAYOUT           = 'payout';
    const PAYOUT_VERIFY    = 'payout_verify';
    const VERIFY_REFUND    = 'verify_refund';
    const OMNI_PAY         = 'omni_pay';
    const CREATE_TERMINAL  = 'create_terminal';
    const ENABLE_TERMINAL  = 'enable_terminal';
    const DISABLE_TERMINAL = 'disable_terminal';
    const DEBIT            = 'debit';
    const PRE_DEBIT        = 'pre_debit';
    const MANDATE_CANCEL   = 'mandate_cancel';
    const AUTHORIZE_FAILED = 'authorize_failed';
    const INTENT_QR        = 'intent_qr';
    const FORCE_AUTHORIZE_FAILED        = 'force_authorize_failed';

    public static $nonVerifiableActions = [
        self::AUTHENTICATE,
        self::PRE_DEBIT,
        self::FORCE_AUTHORIZE_FAILED
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
        self::PAY,
        self::CAPTURE,
        self::VERIFY,
        self::DEBIT,
        self::OTP_RESEND,
        self::AUTHORIZE_FAILED,
        self::FORCE_AUTHORIZE_FAILED
    ];

    public static $upiPaymentServiceSupportedActions = [
        self::AUTHORIZE
    ];
}
