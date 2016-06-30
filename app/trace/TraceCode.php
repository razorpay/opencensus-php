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
    const PAYMENT_REFUND_REQUEST                    = 'PAYMENT_REFUND_REQUEST';
    const PAYMENT_REFUND_SUCCESS                    = 'PAYMENT_REFUND_SUCCESS';
    const PAYMENT_REFUND_FAILURE                    = 'PAYMENT_REFUND_FAILURE';
    const PAYMENT_VERIFY_REFUND_FAILURE             = 'PAYMENT_VERIFY_REFUND_FAILURE';
    const PAYMENT_TOPUP_REQUEST                     = 'PAYMENT_TOPUP_REQUEST';
    const PAYMENT_TOPUP_RESPONSE                    = 'PAYMENT_TOPUP_RESPONSE';
    const PAYMENT_TOPUP_FAILURE                     = 'PAYMENT_TOPUP_FAILURE';
    const PAYMENT_CAPTURE_REQUEST                   = 'PAYMENT_CAPTURE_REQUEST';
    const PAYMENT_CAPTURE_SUCCESS                   = 'PAYMENT_CAPTURE_SUCCESS';
    const PAYMENT_CAPTURE_FAILURE                   = 'PAYMENT_CAPTURE_FAILURE';
    const PAYMENT_CAPTURE_FORCED                    = 'PAYMENT_CAPTURE_FORCED';
    const PAYMENT_TIMED_OUT                         = 'PAYMENT_TIMED_OUT';
    const PAYMENT_VERIFY_FAILED                     = 'PAYMENT_VERIFY_FAILED';
    const PAYMENT_FAILED                            = 'PAYMENT_FAILED';
    const PAYMENT_CANCELLED                         = 'PAYMENT_CANCELLED';
    const PAYMENT_FAILED_TO_AUTHORIZED              = 'PAYMENT_FAILED_TO_AUTHORIZED';
    const PAYMENT_CALLBACK_FAILURE                  = 'PAYMENT_CALLBACK_FAILURE';
    const PAYMENT_AUTHORIZE_FAILED                  = 'PAYMENT_AUTHORIZE_FAILED';
    const PAYMENT_NOTIFY_FAILED                     = 'PAYMENT_NOTIFY_FAILED';
    const PAYMENT_AUTHORIZE_REMINDER                = 'PAYMENT_AUTHORIZE_REMINDER';
    const PAYMENT_WEBHOOK                           = 'PAYMENT_WEBHOOK';
    const PAYMENT_PRICING_RULE_NOT_FOUND            = 'PAYMENT_PRICING_RULE_NOT_FOUND';
    const PAYMENT_OTP_READ_FAILURE                  = 'PAYMENT_OTP_READ_FAILURE';
    const PAYMENT_PRICING_RULE_SELECTION            = 'PAYMENT_PRICING_RULE_SELECTION';
    const PAYMENT_METADATA                          = 'PAYMENT_METADATA';
    const PAYMENT_CREATE_ON_PUBLIC                  = 'PAYMENT_CREATE_ON_PUBLIC';

    const TERMINAL_EDIT                             = 'TERMINAL_EDIT';
    const MERCHANT_EDIT                             = 'MERCHANT_EDIT';
    const CUSTOMER_EDIT                             = 'CUSTOMER_EDIT';
    const CUSTOMER_TOKEN_EDIT                       = 'CUSTOMER_TOKEN_EDIT';

    const TRANSACTION_REFUND_TRACE                  = 'TRANSACTION_REFUND_TRACE';

    const BAD_REQUEST_INVALID_API_KEY               = 'BAD_REQUEST_INVALID_API_KEY';

    const RUNTIME_ERROR                             = 'RUNTIME_ERROR';

    const NETBANKING_PAYMENT_CALLBACK               = 'NETBANKING_PAYMENT_CALLBACK';

    /*
     * Gateway component error messages
     */

    const GATEWAY_ENROLL_REQUEST                    = 'GATEWAY_ENROLL_REQUEST';
    const GATEWAY_ENROLL_RESPONSE                   = 'GATEWAY_ENROLL_RESPONSE';
    const GATEWAY_ENROLL_ERROR                      = 'GATEWAY_ENROLL_ERROR';
    const GATEWAY_CAPTURE_REQUEST                   = 'GATEWAY_CAPTURE_REQUEST';
    const GATEWAY_CAPTURE_RESPONSE                  = 'GATEWAY_CAPTURE_RESPONSE';
    const GATEWAY_CAPTURE_ERROR                     = 'GATEWAY_CAPTURE_ERROR';
    const GATEWAY_NOT_ENROLLED_REQUEST              = 'GATEWAY_NOT_ENROLLED_REQUEST';
    const GATEWAY_NOT_ENROLLED_RESPONSE             = 'GATEWAY_NOT_ENROLLED_RESPONSE';
    const GATEWAY_NOT_ENROLLED_ERROR                = 'GATEWAY_NOT_ENROLLED_ERROR';
    const GATEWAY_ENROLLED_AUTH_REQUEST             = 'GATEWAY_ENROLLED_AUTH_REQUEST';
    const GATEWAY_ENROLLED_AUTH_RESPONSE            = 'GATEWAY_ENROLLED_AUTH_RESPONSE';
    const GATEWAY_ENROLLED_AUTH_ERROR               = 'GATEWAY_ENROLLED_AUTH_ERROR';
    const GATEWAY_VALIDATE_RESPONSE                 = 'GATEWAY_VALIDATE_RESPONSE';
    const GATEWAY_VALIDATE_REQUEST                  = 'GATEWAY_VALIDATE_REQUEST';
    const GATEWAY_VALIDATE_ERROR                    = 'GATEWAY_VALIDATE_ERROR';
    const GATEWAY_AUTHORIZE_RESPONSE                = 'GATEWAY_AUTHORIZE_RESPONSE';
    const GATEWAY_AUTHORIZE_REQUEST                 = 'GATEWAY_AUTHORIZE_REQUEST';
    const GATEWAY_AUTHORIZE_ERROR                   = 'GATEWAY_AUTHORIZE_ERROR';
    const GATEWAY_AUTH_REQUEST                      = 'GATEWAY_AUTH_REQUEST';
    const GATEWAY_SUPPORT_REQUEST                   = 'GATEWAY_SUPPORT_REQUEST';
    const GATEWAY_SUPPORT_RESPONSE                  = 'GATEWAY_SUPPORT_RESPONSE';
    const GATEWAY_SUPPORT_ERROR                     = 'GATEWAY_SUPPORT_ERROR';
    const GATEWAY_UNKNOWN_ERROR                     = 'GATEWAY_UNKNOWN_ERROR';
    const GATEWAY_PAYMENT_AUTHORIZE                 = 'GATEWAY_PAYMENT_AUTHORIZE';
    const GATEWAY_PAYMENT_VERIFY                    = 'GATEWAY_PAYMENT_VERIFY';
    const GATEWAY_PAYMENT_VERIFY_REQUEST            = 'GATEWAY_PAYMENT_VERIFY_REQUEST';
    const GATEWAY_PAYMENT_VERIFY_RESPONSE           = 'GATEWAY_PAYMENT_VERIFY_RESPONSE';
    const GATEWAY_PAYMENT_DATA_PICKUP               = 'GATEWAY_PAYMENT_DATA_PICKUP';
    const GATEWAY_PAYMENT_STATUS_CHANGED            = 'GATEWAY_PAYMENT_STATUS_CHANGED';
    const GATEWAY_PAYMENT_CALLBACK                  = 'GATEWAY_PAYMENT_CALLBACK';
    const GATEWAY_PAYMENT_TOPUP_CALLBACK            = 'GATEWAY_PAYMENT_TOPUP_CALLBACK';
    const GATEWAY_PAYMENT_REFUND                    = 'GATEWAY_PAYMENT_REFUND';
    const GATEWAY_PAYMENT_REQUEST                   = 'GATEWAY_PAYMENT_REQUEST';
    const GATEWAY_PAYMENT_RESPONSE                  = 'GATEWAY_PAYMENT_RESPONSE';
    const GATEWAY_PAYMENT_ERROR                     = 'GATEWAY_PAYMENT_ERROR';
    const GATEWAY_REFUND_ERROR                      = 'GATEWAY_REFUND_ERROR';
    const GATEWAY_REFUND_RESPONSE                   = 'GATEWAY_REFUND_RESPONSE';
    const GATEWAY_REFUND_REQUEST                    = 'GATEWAY_REFUND_REQUEST';
    const GATEWAY_CHECKSUM_VERIFY                   = 'GATEWAY_CHECKSUM_VERIFY';
    const GATEWAY_REQUEST_TIMEOUT                   = 'GATEWAY_REQUEST_TIMEOUT';
    const GATEWAY_RUPAY_CALLBACK                    = 'GATEWAY_RUPAY_CALLBACK';
    const GATEWAY_HDFC_CALLBACK_EMPTY               = 'GATEWAY_HDFC_CALLBACK_EMPTY';
    const GATEWAY_UNSUPPORTED_CARD_NETWORK          = 'GATEWAY_UNSUPPORTED_CARD_NETWORK';

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
    const SETTLEMENT_DAILY_REPORT_DATA              = 'SETTLEMENT_DAILY_REPORT_DATA';
    const SETTLEMENT_DAILY_REPORT_RESULT            = 'SETTLEMENT_DAILY_REPORT_RESULT';

    const MERCHANT_BENEFICIARY_FILE_GENERATE        = 'MERCHANT_BENEFICIARY_FILE_GENERATE';
    const MERCHANT_REPORT_GENERATION                = 'MERCHANT_REPORT_GENERATION';
    const MERCHANT_NOTIFY_HOLIDAY                   = 'MERCHANT_NOTIFY_HOLIDAY';
    const MERCHANT_NEWSLETTER_MAILING_LIST_CREATED  = 'MERCHANT_NEWSLETTER_MAILING_LIST_CREATED';
    const MERCHANT_ACCOUNT_ACTIVATED                = 'MERCHANT_ACCOUNT_ACTIVATED';
    const MERCHANT_TERMINALS                        = 'MERCHANT_TERMINALS';


    const PRICING_PLAN_CREATE_ATTEMPT               = 'PRICING_PLAN_CREATE_ATTEMPT';
    const PRICING_PLAN_CREATE_SUCCESS               = 'PRICING_PLAN_CREATE_SUCCESS';
    const PRICING_PLAN_RULE_ADD_ATTEMPT             = 'PRICING_PLAN_RULE_ADD_ATTEMPT';
    const PRICING_PLAN_RULE_ADD_SUCCESS             = 'PRICING_PLAN_RULE_ADD_SUCCESS';

    const WEBHOOK_FIRING                            = 'WEBHOOK_FIRING';
    const WEBHOOK_FIRED                             = 'WEBHOOK_FIRED';
    const WEBHOOK_DEACTIVATE                        = 'WEBHOOK_DEACTIVATE';
    const WEBHOOK_RESPONSE_FAILURE                  = 'WEBHOOK_RESPONSE_FAILURE';

    const AWS_INSTANCE_DATA_RECORD_FAILURE          = 'AWS_INSTANCE_DATA_RECORD_FAILURE';
    const AWS_INSTANCE_DATA_WRITE_FAILURE           = 'AWS_INSTANCE_DATA_WRITE_FAILURE';
    const AWS_INSTANCE_DATA_READ_FAILURE            = 'AWS_INSTANCE_DATA_READ_FAILURE';

    const DASHBOARD_INTEGRATION_ERROR               = 'DASHBOARD_INTEGRATION_ERROR';
    const DASHBOARD_MERCHANT_APP_AUTH_UNEXPECTED    = 'DASHBOARD_MERCHANT_APP_AUTH_UNEXPECTED';
    const QUEUE_JOB_FAILURE                         = 'QUEUE_JOB_FAILURE';

    const RECOVERABLE_EXCEPTION                     = 'RECOVERABLE_EXCEPTION';
    const ERROR_EXCEPTION                           = 'ERROR_EXCEPTION';
    const ERROR_INVALID_ARGUMENT                    = 'ERROR_INVALID_ARGUMENT';
    const MISC_TRACE_CODE                           = 'MISC_TRACE_CODE';

    const MISC_TOSTRING_ERROR                       = 'MISC_TOSTRING_ERROR';

    const TOKENEX_REQUEST                           = 'TOKENEX_REQUEST';
    const RAVEN_REQUEST                             = 'RAVEN_REQUEST';
    const RAVEN_RESPONSE                            = 'RAVEN_RESPONSE';

    const ES_SAVE_FAILED                            = 'ES_SAVE_FAILED';
    const ES_BULK_UPDATE_FAILED                     = 'ES_BULK_UPDATE_FAILED';
    const ES_BULK_UPDATE                            = 'ES_BULK_UPDATE';
    const ES_SAVE_REQUEST                           = 'ES_SAVE_REQUEST';

    const RECON_ALERT                               = 'RECON_ALERT';
    const RECON_FILE_SKIP                           = 'RECON_FILE_SKIP';
    const RECON_MISMATCH                            = 'RECON_MISMATCH';
    const RECON_PARSE_ERROR                         = 'RECON_PARSE_ERROR';
    const RECON_FAILURE                             = 'RECON_FAILURE';
    const RECON_FAILED_VERIFY                       = 'RECON_FAILED_VERIFY';
    const RECON_FILE_DELETE_FAILURE                 = 'RECON_FILE_DELETE_FAILURE';
    const RECON_INFO_ALERT                          = 'RECON_INFO_ALERT';
    const RECON_CRITICAL_ALERT                      = 'RECON_CRITICAL_ALERT';
    const RECON_REQUEST                             = 'RECON_REQUEST';
    const RECON_FILE_ROW                            = 'RECON_FILE_ROW';
    const RECON_FILE_DETAILS                        = 'RECON_FILE_DETAILS';

    /**
     * Trace code for critical info
     */
    const PAYMENT_NOTES_INVALID                     = 'PAYMENT_NOTES_INVALID';

    protected static $messages = array(
        self::PAYMENT_NEW_REQUEST                   => 'Request for new payment received',
        self::PAYMENT_CREATED                       => 'New payment created',
        self::PAYMENT_CREATE_FAILED                 => 'Payment creation failed',
        self::PAYMENT_AUTH_SUCCESS                  => 'Payment authenticated successfully',
        self::PAYMENT_AUTH_FAILURE                  => 'Payment auth failed',
        self::PAYMENT_FAILED                        => 'Payment failed',
        self::PAYMENT_CANCELLED                     => 'Payment cancelled by user',
        self::PAYMENT_REFUND_SUCCESS                => 'Payment refunded successfully',
        self::PAYMENT_REFUND_FAILURE                => 'Payment refund failed',
        self::PAYMENT_CAPTURE_SUCCESS               => 'Payment captured successfully',
        self::PAYMENT_CAPTURE_FAILURE               => 'Payment capture failed',
        self::PAYMENT_VERIFY_FAILED                 => 'Payment verification with gateway failed',
        self::PAYMENT_FAILED_TO_AUTHORIZED          => 'Payment failed but which succeded on gateway, converting it to authorized',

        self::BAD_REQUEST_INVALID_API_KEY           => 'The api key provided is invalid',

        self::RUNTIME_ERROR                         => 'The request failed at runtime',

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
        self::GATEWAY_UNSUPPORTED_CARD_NETWORK      => 'Card network not supported',

        self::ERROR_EXCEPTION                       => 'Unhandled critical exception occured',
        self::RECOVERABLE_EXCEPTION                 => 'Recoverable exception occurred',
        self::MISC_TRACE_CODE                       => 'Miscellaneous trace code',
        self::ES_SAVE_FAILED                        => 'Failed while trying to save the entity to ES',
        self::ES_BULK_UPDATE_FAILED                 => 'Failed while bulk updating in ES',
        self::ES_BULK_UPDATE                        => 'Bulk update for ES',
        self::ES_SAVE_REQUEST                       => 'Request for saving in ES',

        self::RECON_ALERT                           => 'Alert raised for reconciliation',
        self::RECON_FILE_SKIP                       => 'Skipping a reconciliation file',
        self::RECON_MISMATCH                        => 'Mismatch between the data present in DB and recon file',
        self::RECON_PARSE_ERROR                     => 'Not able to parse some content of the recon file',
        self::RECON_FAILURE                         => 'Reconciliation could not happen',
        self::RECON_FAILED_VERIFY                   => 'Payment verify and authorize was unsuccessful',
        self::RECON_FILE_DELETE_FAILURE             => 'Deleting local file during reconciliation',
        self::RECON_INFO_ALERT                      => 'Info alert raised for reconciliation',
        self::RECON_CRITICAL_ALERT                  => 'Critical alert raised for reconciliation',
        self::RECON_REQUEST                         => 'Request made for reconciliation',
        self::RECON_FILE_ROW                        => 'Row in the reconciliation file that is being reconciled',
        self::RECON_FILE_DETAILS                    => 'Details of all the files collected in the request'
    );

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
        if (defined(TraceCode::class.'::'.$code) === false)
        {
            throw new InvalidArgumentException(
                TraceCode::class.'::'.$code.' not defined');
        }
    }
}
