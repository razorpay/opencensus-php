<?php

namespace RZP\Trace;

use RZP\Models\Payment\Entity as Payment;

class TraceFields
{
    protected static $fields = array(
        TraceCode::ERROR_EXCEPTION => array(
            'class',
            'message',
            'stack',
            'code',
            'data',
        ),

        TraceCode::PAYMENT_NEW_REQUEST => array(
            Payment::MERCHANT_ID,
            Payment::AMOUNT,
        ),

        TraceCode::PAYMENT_CREATED => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
        ),

        TraceCode::PAYMENT_CREATE_FAILED => array(),

        TraceCode::PAYMENT_FAILED => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
            Payment::ERROR_CODE,
            'error'
        ),

        TraceCode::PAYMENT_AUTH_FAILURE => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
            Payment::ERROR_CODE,
            'error'
        ),

        TraceCode::PAYMENT_REFUND_FAILURE => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
            Payment::ERROR_CODE,
            'error'
        ),

        TraceCode::PAYMENT_CAPTURE_FAILURE => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
            Payment::ERROR_CODE,
            'error'
        ),

        TraceCode::PAYMENT_AUTH_SUCCESS => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
        ),

        TraceCode::PAYMENT_REFUND_SUCCESS => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
        ),

        TraceCode::PAYMENT_CAPTURE_SUCCESS => array(
            Payment::ID,
            Payment::MERCHANT_ID,
            Payment::CARD_ID,
            Payment::STATUS,
            Payment::AMOUNT,
        ),

        TraceCode::GATEWAY_ENROLL_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLL_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLL_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_NOT_ENROLLED_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_NOT_ENROLLED_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_NOT_ENROLLED_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_ENROLLED_AUTH_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLLED_AUTH_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_ENROLLED_AUTH_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_SUPPORT_REQUEST => array(
            'url',
            'type',
            'data'
        ),

        TraceCode::GATEWAY_SUPPORT_RESPONSE => array(
            'type',
            'data'
        ),

        TraceCode::GATEWAY_SUPPORT_ERROR => array(
            'type',
            'data',
            'error'
        ),

        TraceCode::GATEWAY_UNKNOWN_ERROR => array(
            'description'
        ),

        TraceCode::DASHBOARD_INTEGRATION_ERROR => array(
            'body',
            'transaction',
            'mode',
        ),

    );

    /**
     * Return fields for a trace event
     *
     * @param string $traceCode event code
     * @return array|mixed
     */
    public static function getFields($traceCode)
    {
        if (isset(self::$fields[$traceCode]) === false)
            return [];

        return self::$fields[$traceCode];
    }

    public static function checkFields($code, $fields)
    {
        $requiredFields = self::getFields($code);

        $missingFields = array_diff($requiredFields, $fields);

        if (count($missingFields) > 0)
        {
            // @todo: Finalize the fields to be logged and remove the extra ones.
            // Currently all log messages have invalid fields defined.
            // throw new Exception\InvalidArgumentException(
            //     implode(',', $missingFields) . ' are missing from trace record');
        }

    }
}
