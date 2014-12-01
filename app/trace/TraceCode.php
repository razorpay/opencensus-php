<?php

namespace Trace;

use EE\Exception\InvalidArgumentException;

class TraceCode
{
    /*
     * Payment component error messages
     */

    const PAYMENT_NEW_REQUEST           = 'PAYMENT_NEW_REQUEST';
    const PAYMENT_CREATED               = 'PAYMENT_CREATED';
    const PAYMENT_CREATE_FAILED         = 'PAYMENT_CREATE_FAILED';
    const PAYMENT_AUTH_SUCCESS          = 'PAYMENT_AUTH_SUCCESS';
    const PAYMENT_AUTH_FAILURE          = 'PAYMENT_AUTH_FAILURE';
    const PAYMENT_REFUND_SUCCESS        = 'PAYMENT_REFUND_SUCCESS';
    const PAYMENT_REFUND_FAILURE        = 'PAYMENT_REFUND_FAILURE';
    const PAYMENT_CAPTURE_SUCCESS       = 'PAYMENT_CAPTURE_SUCCESS';
    const PAYMENT_CAPTURE_FAILURE       = 'PAYMENT_CAPTURE_FAILURE';

    const PAYMENT_FAILED                = 'PAYMENT_FAILED';
    /*
     * Gateway component error messages
     */

    const GATEWAY_ENROLL_REQUEST            = 'GATEWAY_ENROLL_REQUEST';
    const GATEWAY_ENROLL_RESPONSE           = 'GATEWAY_ENROLL_RESPONSE';
    const GATEWAY_ENROLL_ERROR              = 'GATEWAY_ENROLL_ERROR';
    const GATEWAY_NOT_ENROLLED_REQUEST      = 'GATEWAY_NOT_ENROLLED_REQUEST';
    const GATEWAY_NOT_ENROLLED_RESPONSE     = 'GATEWAY_NOT_ENROLLED_RESPONSE';
    const GATEWAY_NOT_ENROLLED_ERROR        = 'GATEWAY_NOT_ENROLLED_ERROR';
    const GATEWAY_ENROLLED_AUTH_REQUEST     = 'GATEWAY_ENROLLED_AUTH_REQUEST';
    const GATEWAY_ENROLLED_AUTH_RESPONSE    = 'GATEWAY_ENROLLED_AUTH_RESPONSE';
    const GATEWAY_ENROLLED_AUTH_ERROR       = 'GATEWAY_ENROLLED_AUTH_ERROR';
    const GATEWAY_SUPPORT_REQUEST           = 'GATEWAY_SUPPORT_REQUEST';
    const GATEWAY_SUPPORT_RESPONSE          = 'GATEWAY_SUPPORT_RESPONSE';
    const GATEWAY_SUPPORT_ERROR             = 'GATEWAY_SUPPORT_ERROR';
    const GATEWAY_UNKNOWN_ERROR             = 'GATEWAY_UNKNOWN_ERROR';

    const ERROR_EXCEPTION                   = 'ERROR_EXCEPTION';

    protected static $messages = array(
        self::PAYMENT_NEW_REQUEST               => 'Request for new payment received',
        self::PAYMENT_CREATED                   => 'New payment created',
        self::PAYMENT_CREATE_FAILED             => 'Payment creation failed',
        self::PAYMENT_AUTH_SUCCESS              => 'Payment authenticated successfully',
        self::PAYMENT_AUTH_FAILURE              => 'Payment auth failed',
        self::PAYMENT_FAILED                    => 'Payment failed',
        self::PAYMENT_REFUND_SUCCESS            => 'Payment refunded successfully',
        self::PAYMENT_REFUND_FAILURE            => 'Payment refund failed',
        self::PAYMENT_CAPTURE_SUCCESS           => 'Payment captured successfully',
        self::PAYMENT_CAPTURE_FAILURE           => 'Payment capture failed',
        self::PAYMENT_FAILED                    => 'Payment failed',

        self::GATEWAY_ENROLL_REQUEST                => 'Request for enrollment sent',
        self::GATEWAY_ENROLL_RESPONSE               => 'Enrollment response received',
        self::GATEWAY_ENROLL_ERROR                  => 'Error in enrollment',
        self::GATEWAY_NOT_ENROLLED_REQUEST          => 'Request for not-enrolled card sent',
        self::GATEWAY_NOT_ENROLLED_RESPONSE         => 'Response for not-enrolled card received',
        self::GATEWAY_NOT_ENROLLED_ERROR            => 'Error occured for not-enrolled card',
        self::GATEWAY_ENROLLED_AUTH_REQUEST         => 'Authentication request sent for enrolled card',
        self::GATEWAY_ENROLLED_AUTH_RESPONSE        => 'Authentication response received for enrolled card',
        self::GATEWAY_ENROLLED_AUTH_ERROR           => 'Authentication error occured for enrolled card',
        self::GATEWAY_SUPPORT_REQUEST               => 'Support request sent',
        self::GATEWAY_SUPPORT_RESPONSE              => 'Support response received',
        self::GATEWAY_SUPPORT_ERROR                 => 'Error in support',
        self::GATEWAY_UNKNOWN_ERROR                 => 'Unknown gateway error',

        self::ERROR_EXCEPTION                       => 'Unhandled critical exception occured');

    /**
     * Translate event code to message
     *
     * @param $eventCode event code
     * @return
     */
    public static function getMessage($code)
    {
        if (! isset(self::$messages[$code]))
        {
            throw new InvalidArgumentException('Message for $code not defined');
        }

        return self::$messages[$code];
    }

    public static function checkCode($code)
    {
        if (! defined(__NAMESPACE__."\TraceCode::$code"))
        {
            throw new InvalidArgumentException(
                __NAMESPACE__.'\TraceCode::'.$code.'not defined');
        }
    }
}