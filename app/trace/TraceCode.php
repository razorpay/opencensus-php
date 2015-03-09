<?php

namespace Trace;

use EE\Exception\InvalidArgumentException;

class TraceCode
{
    /*
     * Payment component error messages
     */

    const PAYMENT_NEW_REQUEST                       = 'PAYMENT_NEW_REQUEST';
    const PAYMENT_CREATED                           = 'PAYMENT_CREATED';
    const PAYMENT_CREATE_FAILED                     = 'PAYMENT_CREATE_FAILED';
    const PAYMENT_AUTH_SUCCESS                      = 'PAYMENT_AUTH_SUCCESS';
    const PAYMENT_AUTH_FAILURE                      = 'PAYMENT_AUTH_FAILURE';
    const PAYMENT_REFUND_SUCCESS                    = 'PAYMENT_REFUND_SUCCESS';
    const PAYMENT_REFUND_FAILURE                    = 'PAYMENT_REFUND_FAILURE';
    const PAYMENT_CAPTURE_SUCCESS                   = 'PAYMENT_CAPTURE_SUCCESS';
    const PAYMENT_CAPTURE_FAILURE                   = 'PAYMENT_CAPTURE_FAILURE';
    const PAYMENT_TIMED_OUT                         = 'PAYMENT_TIMED_OUT';
    const PAYMENT_FAILED                            = 'PAYMENT_FAILED';

    /*
     * Gateway component error messages
     */

    const GATEWAY_ENROLL_REQUEST                    = 'GATEWAY_ENROLL_REQUEST';
    const GATEWAY_ENROLL_RESPONSE                   = 'GATEWAY_ENROLL_RESPONSE';
    const GATEWAY_ENROLL_ERROR                      = 'GATEWAY_ENROLL_ERROR';
    const GATEWAY_NOT_ENROLLED_REQUEST              = 'GATEWAY_NOT_ENROLLED_REQUEST';
    const GATEWAY_NOT_ENROLLED_RESPONSE             = 'GATEWAY_NOT_ENROLLED_RESPONSE';
    const GATEWAY_NOT_ENROLLED_ERROR                = 'GATEWAY_NOT_ENROLLED_ERROR';
    const GATEWAY_ENROLLED_AUTH_REQUEST             = 'GATEWAY_ENROLLED_AUTH_REQUEST';
    const GATEWAY_ENROLLED_AUTH_RESPONSE            = 'GATEWAY_ENROLLED_AUTH_RESPONSE';
    const GATEWAY_ENROLLED_AUTH_ERROR               = 'GATEWAY_ENROLLED_AUTH_ERROR';
    const GATEWAY_SUPPORT_REQUEST                   = 'GATEWAY_SUPPORT_REQUEST';
    const GATEWAY_SUPPORT_RESPONSE                  = 'GATEWAY_SUPPORT_RESPONSE';
    const GATEWAY_SUPPORT_ERROR                     = 'GATEWAY_SUPPORT_ERROR';
    const GATEWAY_UNKNOWN_ERROR                     = 'GATEWAY_UNKNOWN_ERROR';

    const MPR_HDFC_GEN_INITIATED                    = 'MPR_HDFC_GEN_INITIATED';
    const MPR_HDFC_FILE_GENERATED                   = 'MPR_HDFC_FILE_GENERATED';
    const MPR_HDFC_PAYMENTS_FETCHED                 = 'MPR_HDFC_PAYMENTS_FETCHED';
    const MPR_GENERATED                             = 'MPR_GENERATED';
    const MPR_RECONCILED                            = 'MPR_RECONCILED';
    const MPR_RECONCILE_UNRECOGNIZED_CARD_NETWORK   = 'MPR_RECONCILE_UNRECOGNIZED_CARD_NETWORK';
    const SETTLEMENT_INITIATING                     = 'SETTLEMENT_INITIATING';
    const SETTLEMENT_INITIATED                      = 'SETTLEMENT_INITIATED';
    const SETTLEMENT_RECONCILED                     = 'SETTLEMENT_RECONCILED';
    const SETTLEMENT_RETURNED                       = 'SETTLEMENT_RETURNED';
    const SETTLEMENT_INITIATE_FAILED                = 'SETTLEMENT_INITIATE_FAILED';
    const SETTLEMENT_RECONCILIATION_FAILED          = 'SETTLEMENT_RECONCILIATION_FAILED';
    const SETTLEMENT_RETURN_FAILED                  = 'SETTLEMENT_RETURN_FAILED';
    const SETTLEMENT_FILE_GENERATED_KOTAK           = 'SETTLEMENT_FILE_GENERATED_KOTAK';
    const SETTLEMENT_KOTAK_FILE_TRANSFERRED         = 'SETTLEMENT_KOTAK_FILE_TRANSFERRED';
    const SETTLEMENT_KOTAK_FAILURE_DATA_MISSING     = 'SETTLEMENT_KOTAK_FAILURE_DATA_MISSING';
    const SETTLEMENT_ATOM_INITIATED_RECONCILED      = 'SETTLEMENT_ATOM_INITIATED_RECONCILED';
    const SETTLEMENT_MERCHANT_SETL_FAILED           = 'SETTLEMENT_MERCHANT_SETL_FAILED';
    const SETTLEMENT_KOTAK_RECONCILE_FILE_GENERATED = 'SETTLEMENT_KOTAK_RECONCILE_FILE_GENERATED';

    const AWS_INSTANCE_DATA_RECORD_FAILURE          = 'AWS_INSTANCE_DATA_RECORD_FAILURE';
    const AWS_INSTANCE_DATA_WRITE_FAILURE           = 'AWS_INSTANCE_DATA_WRITE_FAILURE';
    const AWS_INSTANCE_DATA_READ_FAILURE            = 'AWS_INSTANCE_DATA_READ_FAILURE';

    const DASHBOARD_INTEGRATION_ERROR               = 'DASHBOARD_INTEGRATION_ERROR';
    const QUEUE_JOB_FAILURE                         = 'QUEUE_JOB_FAILURE';

    const ERROR_EXCEPTION                           = 'ERROR_EXCEPTION';
    const MISC_TRACE_CODE                           = 'MISC_TRACE_CODE';

    protected static $messages = array(
        self::PAYMENT_NEW_REQUEST                   => 'Request for new payment received',
        self::PAYMENT_CREATED                       => 'New payment created',
        self::PAYMENT_CREATE_FAILED                 => 'Payment creation failed',
        self::PAYMENT_AUTH_SUCCESS                  => 'Payment authenticated successfully',
        self::PAYMENT_AUTH_FAILURE                  => 'Payment auth failed',
        self::PAYMENT_FAILED                        => 'Payment failed',
        self::PAYMENT_REFUND_SUCCESS                => 'Payment refunded successfully',
        self::PAYMENT_REFUND_FAILURE                => 'Payment refund failed',
        self::PAYMENT_CAPTURE_SUCCESS               => 'Payment captured successfully',
        self::PAYMENT_CAPTURE_FAILURE               => 'Payment capture failed',
        self::PAYMENT_FAILED                        => 'Payment failed',

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

        self::ERROR_EXCEPTION                       => 'Unhandled critical exception occured',
        self::MISC_TRACE_CODE                       => 'Miscellaneous trace code');

    /**
     * Translate event code to message
     *
     * @param $eventCode event code
     * @return
     */
    public static function getMessage($code)
    {
        if (isset(self::$messages[$code]) === false)
        {
            // throw new InvalidArgumentException('Message for $code not defined');
            return null;
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