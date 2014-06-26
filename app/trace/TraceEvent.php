<?php

namespace Trace;

class TraceEvent
{
    /*
     * Transaction component error messages
     */

    const TRANSACTION_NEW_REQUEST           = 'TRANSACTION_NEW_REQUEST';
    const TRANSACTION_CREATED               = 'TRANSACTION_CREATED';
    const TRANSACTION_CREATE_FAILED         = 'TRANSACTION_CREATE_FAILED';
    const TRANSACTION_AUTH_SUCCESS                = 'TRANSACTION_AUTH_SUCCESS';
    const TRANSACTION_AUTH_FAILED           = 'TRANSACTION_AUTH_FAILED';
    const TRANSACTION_REFUND_SUCCESS              = 'TRANSACTION_REFUND_SUCCESS';
    const TRANSACTION_REFUND_FAILED         = 'TRANSACTION_REFUND_FAILED';
    const TRANSACTION_CAPTURE_SUCCESS              = 'TRANSACTION_CAPTURE_SUCCESS';
    const TRANSACTION_CAPTURE_FAILED        = 'TRANSACTION_CAPTURE_FAILED';
    const TRANSACTION_EXCEPTION             = 'TRANSACTION_EXCEPTION';

    const TRANSACTION_FAILED                = 'TRANSACTION_FAILED';
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
    const GATEWAY_EXCEPTION                 = 'GATEWAY_EXCEPTION';


    protected static $messages = array(
        self::TRANSACTION_NEW_REQUEST       => 'Request for new transaction received',
        self::TRANSACTION_CREATED           => 'New transaction created',
        self::TRANSACTION_CREATE_FAILED     => 'Transaction creation failed',
        self::TRANSACTION_AUTH_SUCCESS            => 'Transaction authenticated successfully',
        self::TRANSACTION_AUTH_FAILED       => 'Transaction auth failed',
        self::TRANSACTION_FAILED            => 'Transaction failed',
        self::TRANSACTION_REFUND_SUCCESS          => 'Transaction refunded successfully',
        self::TRANSACTION_REFUND_FAILED     => 'Transaction refund failed',
        self::TRANSACTION_CAPTURE_SUCCESS          => 'Transaction captured successfully',
        self::TRANSACTION_CAPTURE_FAILED    => 'Transaction capture failed',
        self::TRANSACTION_EXCEPTION         => 'Transaction exception occured',
        self::TRANSACTION_FAILED            => 'Transaction failed',

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
        self::GATEWAY_EXCEPTION                     => 'Gateway exception occured');

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
            throw new \InvalidArgumentException('Message for $code not defined');
        }

        return self::$messages[$code];
    }

    public static function checkCode($code)
    {
        if (! defined(__NAMESPACE__."\TraceEvent::$code"))
        {
            throw new \InvalidArgumentException(__NAMESPACE__."\TraceEvent::$code not defined");
        }
    }
}