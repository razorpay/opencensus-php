<?php

namespace RZP\Gateway\Base;

class Action
{
    const PURCHASE      = 'purchase';
    const AUTHENTICATE  = 'authenticate';
    const AUTHORIZE     = 'authorize';
    const CAPTURE       = 'capture';
    const REFUND        = 'refund';
    const VOID          = 'void';
    const VERIFY        = 'verify';
    const CALLBACK      = 'callback';
    const REVERSE       = 'reverse';
    const OTP_RESEND    = 'otp_resend';
    const OTP_GENERATE  = 'otp_generate';
    const VALIDATE_PUSH = 'validate_push';

    public static $nonVerifiableActions = [
        self::AUTHENTICATE
    ];
}
