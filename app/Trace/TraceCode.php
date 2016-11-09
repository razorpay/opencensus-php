<?php

namespace RZP\Trace;

use RZP\Exception\InvalidArgumentException;

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
    const PAYMENT_CALLBACK_REQUEST                  = 'PAYMENT_CALLBACK_REQUEST';
    const PAYMENT_REFUND_REQUEST                    = 'PAYMENT_REFUND_REQUEST';
    const PAYMENT_REFUND_SUCCESS                    = 'PAYMENT_REFUND_SUCCESS';
    const PAYMENT_REFUND_FAILURE                    = 'PAYMENT_REFUND_FAILURE';
    const PAYMENT_VERIFY_CAPTURE_FAILURE            = 'PAYMENT_VERIFY_CAPTURE_FAILURE';
    const PAYMENT_VERIFY_REFUND_FAILURE             = 'PAYMENT_VERIFY_REFUND_FAILURE';
    const PAYMENT_TOPUP_REQUEST                     = 'PAYMENT_TOPUP_REQUEST';
    const PAYMENT_TOPUP_RESPONSE                    = 'PAYMENT_TOPUP_RESPONSE';
    const PAYMENT_TOPUP_FAILURE                     = 'PAYMENT_TOPUP_FAILURE';
    const PAYMENT_CAPTURE_REQUEST                   = 'PAYMENT_CAPTURE_REQUEST';
    const PAYMENT_CAPTURE_SUCCESS                   = 'PAYMENT_CAPTURE_SUCCESS';
    const PAYMENT_CAPTURE_FAILURE                   = 'PAYMENT_CAPTURE_FAILURE';
    const PAYMENT_CAPTURE_FORCED                    = 'PAYMENT_CAPTURE_FORCED';
    const PAYMENT_ALREADY_CAPTURED                  = 'PAYMENT_ALREADY_CAPTURED';
    const PAYMENT_AUTO_CAPTURE                      = 'PAYMENT_AUTO_CAPTURE';
    const PAYMENT_AUTO_CAPTURE_FAILED               = 'PAYMENT_AUTO_CAPTURE_FAILED';
    const PAYMENT_QUEUE_CAPTURE_REQUEST             = 'PAYMENT_QUEUE_CAPTURE_REQUEST';
    const PAYMENT_QUEUE_CAPTURE_SUCCESS             = 'PAYMENT_QUEUE_CAPTURE_SUCCESS';
    const PAYMENT_QUEUE_CAPTURE_FAILURE             = 'PAYMENT_QUEUE_CAPTURE_FAILURE';
    const PAYMENT_CAPTURE_FAILURE_EXCEPTION         = 'PAYMENT_CAPTURE_FAILURE_EXCEPTION';
    const PAYMENT_QUEUE_CAPTURE_DELETE              = 'PAYMENT_QUEUE_CAPTURE_DELETE';
    const PAYMENT_CAPTURE_ADD_TO_QUEUE              = 'PAYMENT_CAPTURE_ADD_TO_QUEUE';
    const PAYMENT_TIMED_OUT                         = 'PAYMENT_TIMED_OUT';
    const PAYMENT_VERIFY_FAILED                     = 'PAYMENT_VERIFY_FAILED';
    const PAYMENT_FAILED                            = 'PAYMENT_FAILED';
    const PAYMENT_CANCELLED                         = 'PAYMENT_CANCELLED';
    const PAYMENT_CANCELLED_METADATA                = 'PAYMENT_CANCELLED_METADATA';
    const PAYMENT_FAILED_TO_AUTHORIZED              = 'PAYMENT_FAILED_TO_AUTHORIZED';
    const PAYMENT_CALLBACK_FAILURE                  = 'PAYMENT_CALLBACK_FAILURE';
    const PAYMENT_CALLBACK_RETRY                    = 'PAYMENT_CALLBACK_RETRY';
    const PAYMENT_CALLBACK_RETRY_SUCCESS            = 'PAYMENT_CALLBACK_RETRY_SUCCESS';
    const PAYMENT_AUTHORIZE_FAILED                  = 'PAYMENT_AUTHORIZE_FAILED';
    const PAYMENT_NOTIFY_FAILED                     = 'PAYMENT_NOTIFY_FAILED';
    const PAYMENT_AUTHORIZE_REMINDER                = 'PAYMENT_AUTHORIZE_REMINDER';
    const PAYMENT_AUTHORIZE_REMINDER_FAILURE        = 'PAYMENT_AUTHORIZE_REMINDER_FAILURE';
    const PAYMENT_CHECKOUT_INVALID_ID               = 'PAYMENT_CHECKOUT_INVALID_ID';
    const PAYMENT_WEBHOOK                           = 'PAYMENT_WEBHOOK';
    const PAYMENT_OTP_READ_FAILURE                  = 'PAYMENT_OTP_READ_FAILURE';
    const PAYMENT_METADATA                          = 'PAYMENT_METADATA';
    const PAYMENT_CREATE_ON_PUBLIC                  = 'PAYMENT_CREATE_ON_PUBLIC';
    const PAYMENT_CARD_NOT_ENROLLED                 = 'PAYMENT_CARD_NOT_ENROLLED';
    const PAYMENT_INVALID_CONTACT_NUMBER            = 'PAYMENT_INVALID_CONTACT_NUMBER';
    const PAYMENT_CAPTURE_CREATE_TRANSACTION        = 'PAYMENT_CAPTURE_CREATE_TRANSACTION';
    const PAYMENT_CAPTURE_UPDATE_TRANSACTION        = 'PAYMENT_CAPTURE_UPDATE_TRANSACTION';
    const PAYMENT_CAPTURE_ORDER_UPDATE              = 'PAYMENT_CAPTURE_ORDER_UPDATE';
    const PAYMENT_TRANSACTION_OLD                   = 'PAYMENT_TRANSACTION_OLD';
    const PAYMENT_USER_AGENT_ANOMALY                = 'PAYMENT_USER_AGENT_ANOMALY';
    const PAYMENT_CARD_IIN_MISSING                  = 'PAYMENT_CARD_IIN_MISSING';
    const TRANSACTION_CREATED_IN_VERIFY_CAPTURE     = 'TRANSACTION_CREATED_IN_VERIFY_CAPTURE';
    const TRANSACTION_FREE_CREDITS                  = 'TRANSACTION_FREE_CREDITS';
    const PAYMENT_NOT_CAPTURED_CREATE_TRANSACTION   = 'PAYMENT_NOT_CAPTURED_CREATE_TRANSACTION';
    const VERIFY_CAPTURE_RESPONSE                   = 'VERIFY_CAPTURE_RESPONSE';
    const PAYMENT_ANALYTICS_SAVE_FAILED             = 'PAYMENT_ANALYTICS_SAVE_FAILED';
    const PAYMENT_ANALYTICS_UNRECOGNIZED_DATA       = 'PAYMENT_ANALYTICS_UNRECOGNIZED_DATA';
    const PAYMENT_ANALYTICS_INCORRECT_DATA          = 'PAYMENT_ANALYTICS_INCORRECT_DATA';
    const TERMINAL_ANALYTICS_SAVE_FAILED            = 'TERMINAL_ANALYTICS_SAVE_FAILED';
    const VERIFY_REFUND_TRANSACTION_CREATED         = 'VERIFY_REFUND_TRANSACTION_CREATED';
    const MANUAL_GATEWAY_REFUND_RESPONSE            = 'MANUAL_GATEWAY_REFUND_RESPONSE';
    const MANUAL_GATEWAY_ALL_REFUNDS_RESPONSE       = 'MANUAL_GATEWAY_ALL_REFUNDS_RESPONSE';
    const MANUAL_GATEWAY_REFUND_FAILURE             = 'MANUAL_GATEWAY_REFUND_FAILURE';
    const MANUAL_GATEWAY_REFUND_INITIATED           = 'MANUAL_GATEWAY_REFUND_INITIATED';
    const REFUND_GATEWAY_REQUIRED                   = 'REFUND_GATEWAY_REQUIRED';
    const PAYMENT_REQUEST_CHECKOUT_ID_NOT_FOUND     = 'PAYMENT_REQUEST_CHECKOUT_ID_NOT_FOUND';
    const PAYMENT_AUTO_REFUND_FAILURE               = 'PAYMENT_AUTO_REFUND_FAILURE';
    const FORCE_AUTHORIZE_TIMEOUT_PAYMENTS_RESPONSE = 'FORCE_AUTHORIZE_TIMEOUT_PAYMENTS_RESPONSE';
    const REFUND_FILE_GENERATE_REQUEST              = 'REFUND_FILE_GENERATE_REQUEST';
    const RECONCILE_CANCELLED_TRANSACTIONS          = 'RECONCILE_CANCELLED_TRANSACTIONS';
    const ORDER_REFUNDED                            = 'ORDER_REFUNDED';
    const WEBHOOK_EDIT                              = 'WEBHOOK_EDIT';

    const ORDER_MULTIPLE_CAPTURED_PAYMENTS          = 'ORDER_MULTIPLE_CAPTURED_PAYMENTS';

    const ORDER_CREATE_REQUEST                      = 'ORDER_CREATE_REQUEST';
    const REFUND_TRANSACTION_CREATED                = 'REFUND_TRANSACTION_CREATED';

    const TERMINAL_SELECTION                        = 'TERMINAL_SELECTION';
    const TERMINAL_SELECTION_MISMATCH               = 'TERMINAL_SELECTION_MISMATCH';
    const TERMINAL_FAILURE                          = 'TERMINAL_FAILURE';
    const TERMINAL_EDIT                             = 'TERMINAL_EDIT';
    const TERMINAL_ENABLE                           = 'TERMINAL_ENABLE';
    const TERMINAL_DISABLE                          = 'TERMINAL_DISABLE';
    const TERMINAL_FAIL_SORT                        = 'TERMINAL_FAIL_SORT';
    const MERCHANT_EDIT                             = 'MERCHANT_EDIT';
    const CUSTOMER_EDIT                             = 'CUSTOMER_EDIT';
    const CUSTOMER_TOKEN_EDIT                       = 'CUSTOMER_TOKEN_EDIT';
    const CARD_NUMBER_SCRUBBED                      = 'CARD_NUMBER_SCRUBBED';
    const ORDERS_MULTIPLE_AUTHORIZED_REFUNDS        = 'ORDERS_MULTIPLE_AUTHORIZED_REFUNDS';
    const REFUND_EXCEPTION                          = 'REFUND_EXCEPTION';

    const TRANSACTION_REFUND_TRACE                  = 'TRANSACTION_REFUND_TRACE';

    const BAD_REQUEST_INVALID_API_KEY               = 'BAD_REQUEST_INVALID_API_KEY';
    const BAD_REQUEST_INVALID_API_SECRET            = 'BAD_REQUEST_INVALID_API_SECRET';
    const BAD_REQUEST_API_SECRET_NOT_PROVIDED       = 'BAD_REQUEST_API_SECRET_NOT_PROVIDED';

    const RUNTIME_ERROR                             = 'RUNTIME_ERROR';

    const NETBANKING_PAYMENT_CALLBACK               = 'NETBANKING_PAYMENT_CALLBACK';

    const CHECKOUT_PREFERENCES_REQUEST              = 'CHECKOUT_PREFERENCES_REQUEST';
    const CHECKOUT_PREFERENCES_COOKIE_CHECK         = 'CHECKOUT_PREFERENCES_COOKIE_CHECK';

    // Card Saving related
    const PAYMENT_FILL_SAVED_APP_TOKEN              = 'PAYMENT_FILL_SAVED_APP_TOKEN';
    const PAYMENT_GET_CUSTOMER                      = 'PAYMENT_GET_CUSTOMER';
    const CUSTOMER_SESSION                          = 'CUSTOMER_SESSION';
    const CUSTOMER_CREATE_APP_TOKEN                 = 'CUSTOMER_CREATE_APP_TOKEN';
    const CUSTOMER_CHECKCOOKIE_STATUS               = 'CUSTOMER_CHECKCOOKIE_STATUS';
    const PAYMENT_PROCESS_FROM_SAVED_LOCAL          = 'PAYMENT_PROCESS_FROM_SAVED_LOCAL';
    const PAYMENT_PROCESS_FROM_SAVED_GLOBAL         = 'PAYMENT_PROCESS_FROM_SAVED_GLOBAL';
    const PAYMENT_SAVE_METHOD                       = 'PAYMENT_SAVE_METHOD';
    const PAYMENT_APP_TOKEN_NOT_FOUND               = 'PAYMENT_APP_TOKEN_NOT_FOUND';
    const PAYMENT_UPDATE_TOKEN                      = 'PAYMENT_UPDATE_TOKEN';

    //Pricing
    const PRICING_RULE_SELECTION                    = 'PRICING_RULE_SELECTION';
    const PAYMENT_PRICING_RULE_NOT_FOUND            = 'PAYMENT_PRICING_RULE_NOT_FOUND';
    const PAYMENT_PRICING_RULE_SELECTION            = 'PAYMENT_PRICING_RULE_SELECTION';

    const ADDRESS_CREATE_REQUEST                    = 'ADDRESS_CREATE_REQUEST';
    const ADDRESS_PRIMARY_SWITCH                    = 'ADDRESS_PRIMARY_SWITCH';
    const ADDRESS_DELETE_REQUEST                    = 'ADDRESS_DELETE_REQUEST';

    //Adjustments
    const ADJUSTMENT_CREATE_REQUEST                 = 'ADJUSTMENT_CREATE_REQUEST';
    const ADJUSTMENT_CREATE_SUCCESS                 = 'ADJUSTMENT_CREATE_SUCCESS';

    const VERIFY_LOCKED_PAYMENTS                    = 'VERIFY_LOCKED_PAYMENTS';
    const VERIFY_PROCESSED_SUMMARY                  = 'VERIFY_PROCESSED_SUMMARY';
    const PAYMENT_VERIFY_RESULT                     = 'PAYMENT_VERIFY_RESULT';
    const PAYMENT_VERIFY_ALREADY_AUTHORIZED         = 'PAYMENT_VERIFY_ALREADY_AUTHORIZED';

    /*
     * Gateway component error messages
     */

    const GATEWAY_RESPONSE                          = 'GATEWAY_RESPONSE';
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
    const GATEWAY_CHECKSUM_VERIFY_REQUEST           = 'GATEWAY_CHECKSUM_VERIFY_REQUEST';
    const GATEWAY_CHECKSUM_VERIFY_FAILED            = 'GATEWAY_CHECKSUM_VERIFY_FAILED';
    const GATEWAY_SOAP_REQUEST                      = 'GATEWAY_SOAP_REQUEST';
    const GATEWAY_REQUEST_TIMEOUT                   = 'GATEWAY_REQUEST_TIMEOUT';
    const GATEWAY_RUPAY_CALLBACK                    = 'GATEWAY_RUPAY_CALLBACK';
    const GATEWAY_HDFC_CALLBACK_EMPTY               = 'GATEWAY_HDFC_CALLBACK_EMPTY';
    const GATEWAY_UNSUPPORTED_CARD_NETWORK          = 'GATEWAY_UNSUPPORTED_CARD_NETWORK';
    const GATEWAY_PAYMENT_VERIFY_UNEXPECTED         = 'GATEWAY_PAYMENT_VERIFY_UNEXPECTED';
    const GATEWAY_ABSENCE_CREATE                    = 'GATEWAY_ABSENCE_CREATE';
    const GATEWAY_ABSENCE_EDIT                      = 'GATEWAY_ABSENCE_EDIT';
    const GATEWAY_VERIFY_INVALID_HEADER             = 'GATEWAY_VERIFY_INVALID_HEADER';
    const GATEWAY_ABSENCE_DELETE                    = 'GATEWAY_ABSENCE_DELETE';

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
    const SETTLEMENT_DAILY_REPORT_MAILING           = 'SETTLEMENT_DAILY_REPORT_MAILING';
    const SETTLEMENT_DAILY_REPORT_DATA              = 'SETTLEMENT_DAILY_REPORT_DATA';
    const SETTLEMENT_DAILY_REPORT_RESULT            = 'SETTLEMENT_DAILY_REPORT_RESULT';
    const SETTLEMENT_DAILY_REPORT_FAILURE           = 'SETTLEMENT_DAILY_REPORT_FAILURE';
    const CLIENT_CERTIFICATE_FILE_GENERATED         = 'CLIENT_CERTIFICATE_FILE_GENERATED';
    const EMI_FILE_SENT                             = 'EMI_FILE_SENT';

    const SCHEDULE_RESOLUTION_INITIATED             = 'SCHEDULE_RESOLUTION_INITIATED';
    const SCHEDULE_ANCHORED_RESOLUTION              = 'SCHEDULE_ANCHORED_RESOLUTION';
    const SCHEDULE_UNANCHORED_RESOLUTION            = 'SCHEDULE_UNANCHORED_RESOLUTION';
    const SCHEDULE_ASSIGN_REQUEST                   = 'SCHEDULE_ASSIGN_REQUEST';
    const SCHEDULE_CREATE_REQUEST                   = 'SCHEDULE_CREATE_REQUEST';
    const SCHEDULE_EDIT_REQUEST                     = 'SCHEDULE_EDIT_REQUEST';
    const SCHEDULE_CREATED                          = 'SCHEDULE_CREATED';
    const SCHEDULE_EDITED                           = 'SCHEDULE_EDITED';
    const SCHEDULE_ASSIGNED                         = 'SCHEDULE_ASSIGNED';
    const SCHEDULE_NEXT_RUN_UPDATED                 = 'SCHEDULE_NEXT_RUN_UPDATED';
    const SCHEDULE_UNSETTLED_TXNS_FETCH             = 'SCHEDULE_UNSETTLED_TXNS_FETCH';
    const SCHEDULE_UNSETTLED_TXNS                   = 'SCHEDULE_UNSETTLED_TXNS';
    const SCHEDULE_MIGRATION_INITIATED              = 'SCHEDULE_MIGRATION_INITIATED';
    const SCHEDULE_MIGRATION_FAILED                 = 'SCHEDULE_MIGRATION_FAILED';
    const SCHEDULE_MIGRATION_COMPLETE               = 'SCHEDULE_MIGRATION_COMPLETE';

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
    const AWS_S3_LOGO_UPLOAD                        = 'AWS_S3_LOGO_UPLOAD';
    const AWS_FILE_UPLOAD                           = 'AWS_FILE_UPLOAD';
    const AWS_FILE_DOWNLOAD                         = 'AWS_FILE_DOWNLOAD';

    const DASHBOARD_INTEGRATION_ERROR               = 'DASHBOARD_INTEGRATION_ERROR';
    const DASHBOARD_MERCHANT_APP_AUTH_UNEXPECTED    = 'DASHBOARD_MERCHANT_APP_AUTH_UNEXPECTED';
    const QUEUE_JOB_FAILURE                         = 'QUEUE_JOB_FAILURE';
    const QUEUE_JOB_LOOPING                         = 'QUEUE_JOB_LOOPING';

    const RECOVERABLE_EXCEPTION                     = 'RECOVERABLE_EXCEPTION';
    const ERROR_EXCEPTION                           = 'ERROR_EXCEPTION';
    const ERROR_INVALID_ARGUMENT                    = 'ERROR_INVALID_ARGUMENT';
    const MISC_TRACE_CODE                           = 'MISC_TRACE_CODE';
    const REFUND_TRANSACTION_FAILED                 = 'REFUND_TRANSACTION_FAILED';

    const MISC_TOSTRING_ERROR                       = 'MISC_TOSTRING_ERROR';

    const TOKENEX_REQUEST                           = 'TOKENEX_REQUEST';
    const RAVEN_REQUEST                             = 'RAVEN_REQUEST';
    const RAVEN_RESPONSE                            = 'RAVEN_RESPONSE';

    const MAXMIND_RESPONSE                          = 'MAXMIND_RESPONSE';

    const ES_SAVE_FAILED                            = 'ES_SAVE_FAILED';
    const ES_BULK_UPDATE_FAILED                     = 'ES_BULK_UPDATE_FAILED';
    const ES_BULK_UPDATE                            = 'ES_BULK_UPDATE';
    const ES_SAVE_REQUEST                           = 'ES_SAVE_REQUEST';

    const RECON_ALERT                               = 'RECON_ALERT';
    const RECON_FILE_SKIP                           = 'RECON_FILE_SKIP';
    const RECON_MISMATCH                            = 'RECON_MISMATCH';
    const RECON_INFO                                = 'RECON_INFO';
    const RECON_PARSE_ERROR                         = 'RECON_PARSE_ERROR';
    const RECON_FAILURE                             = 'RECON_FAILURE';
    const RECON_FAILED_VERIFY                       = 'RECON_FAILED_VERIFY';
    const RECON_FILE_DELETE_FAILURE                 = 'RECON_FILE_DELETE_FAILURE';
    const RECON_INFO_ALERT                          = 'RECON_INFO_ALERT';
    const RECON_CRITICAL_ALERT                      = 'RECON_CRITICAL_ALERT';
    const RECON_REQUEST                             = 'RECON_REQUEST';
    const RECON_FILE_ROW                            = 'RECON_FILE_ROW';
    const RECON_FILE_DETAILS                        = 'RECON_FILE_DETAILS';
    const RECON_INFO_SUMMARY                        = 'RECON_INFO_SUMMARY';
    const IIN_INSERT_FAILED                         = 'IIN_INSERT_FAILED';

    //Trace code for Transaction Migration
    const TRANSACTION_MIGRATION_TAX_MISTMATCH       = 'TRANSACTION_MIGRATION_TAX_MISTMATCH';
    const TRANSACTION_MIGRATION_FEE_MISTMATCH       = 'TRANSACTION_MIGRATION_FEE_MISTMATCH';

    const BATCH_UPLOAD_FILE_ENTRIES                 = 'BATCH_UPLOAD_FILE_ENTRIES';
    const BATCH_UPLOAD_FILE                         = 'BATCH_UPLOAD_FILE';
    const BATCH_PROCESS_FILE                        = 'BATCH_PROCESS_FILE';
    const BATCH_ALREADY_PROCESSED                   = 'BATCH_ALREADY_PROCESSED';
    const BATCH_PROCESSING_ERROR                    = 'BATCH_PROCESSING_ERROR';
    const BATCH_RETRY                               = 'BATCH_RETRY';
    const BATCH_RETRY_FAILURE                       = 'BATCH_RETRY_FAILURE';
    const BATCH_DOWNLOAD                            = 'BATCH_DOWNLOAD';
    const BATCH_LIST                                = 'BATCH_LIST';
    const BATCH_GET                                 = 'BATCH_GET';
    const BATCH_FILE_DELETE                         = 'BATCH_FILE_DELETE';

    const MUTEX_LOCK_ALREADY_RELEASED               = 'MUTEX_LOCK_ALREADY_RELEASED';

    /**
     * Trace code for critical info
     */
    const PAYMENT_NOTES_INVALID                     = 'PAYMENT_NOTES_INVALID';

    /**
     * Additional trace codes for Segment integration
     */
    const GATEWAY_SELECTION_PREPROCESSING           = 'SEGMENT_GATEWAY_SELECTION_PREPROCESSING';
    const GATEWAY_POSTPROCESSING                    = 'GATEWAY_POSTPROCESSING';
    const OTP_GENERATE                              = 'OTP_GENERATE';
    const OTP_POSTPROCESSING                        = 'OTP_POSTPROCESSING';
    const OTP_RESEND_EXCEPTION                      = 'OTP_RESEND_EXCEPTION';
    const OTP_RESEND                                = 'OTP_RESEND';
    const TERMINAL_SUCCESS                          = 'TERMINAL_SUCCESS';
    const PAYMENT_CALL_GATEWAY_FUNC                 = 'PAYMENT_CALL_GATEWAY_FUNC';
    const ASYNC_PAYMENT_RESPONSE                    = 'ASYNC_PAYMENT_RESPONSE';
    const FIRST_PAYMENT_RESPONSE                    = 'FIRST_PAYMENT_RESPONSE';
    const FORCE_AUTH_FAILED_PAYMENT                 = 'FORCE_AUTH_FAILED_PAYMENT';
    const PAYMENT_FAILED_EXPECTED_GATEWAY_SUCCESS   = 'PAYMENT_FAILED_EXPECTED_GATEWAY_SUCCESS';
    const PAYMENT_ALREADY_AUTHORIZED                = 'PAYMENT_ALREADY_AUTHORIZED';
    const SEGMENT_POST_FAILED                       = 'SEGMENT_POST_FAILED';

    // Trace code for features
    const FEATURE_DELETE_REQUEST                    = 'FEATURE_DELETE_REQUEST';
    const FEATURE_MIGRATION_EXCEPTION               = 'FEATURE_MIGRATION_EXCEPTION';
    const FEATURE_ASSIGNMENT_EXCEPTION              = 'FEATURE_ASSIGNMENT_EXCEPTION';

    protected static $messages = array(
        self::PAYMENT_NEW_REQUEST                       => 'Request for new payment received',
        self::PAYMENT_CREATED                           => 'New payment created',
        self::PAYMENT_CREATE_FAILED                     => 'Payment creation failed',
        self::PAYMENT_AUTH_SUCCESS                      => 'Payment authenticated successfully',
        self::PAYMENT_AUTH_FAILURE                      => 'Payment auth failed',
        self::PAYMENT_FAILED                            => 'Payment failed',
        self::PAYMENT_CANCELLED                         => 'Payment cancelled by user',
        self::PAYMENT_REFUND_SUCCESS                    => 'Payment refunded successfully',
        self::PAYMENT_REFUND_FAILURE                    => 'Payment refund failed',
        self::PAYMENT_CAPTURE_SUCCESS                   => 'Payment captured successfully',
        self::PAYMENT_CAPTURE_FAILURE                   => 'Payment capture failed',
        self::PAYMENT_VERIFY_FAILED                     => 'Payment verification with gateway failed',
        self::PAYMENT_FAILED_TO_AUTHORIZED              => 'Payment failed but which succeeded on gateway, converting it to authorized',
        self::PAYMENT_REQUEST_CHECKOUT_ID_NOT_FOUND     => 'Payment request does not have checkout id',
        self::PAYMENT_QUEUE_CAPTURE_REQUEST             => 'Payment capture request via queue',
        self::PAYMENT_QUEUE_CAPTURE_SUCCESS             => 'Payment captured successfully via queue',
        self::PAYMENT_QUEUE_CAPTURE_FAILURE             => 'Payment failed to capture via queue',
        self::PAYMENT_CAPTURE_FAILURE_EXCEPTION         => 'Payment failed to capture because of an exception',
        self::PAYMENT_QUEUE_CAPTURE_DELETE              => 'Deleting the capture request from the queue',
        self::PAYMENT_CAPTURE_REQUEST                   => 'Payment capture request received',
        self::PAYMENT_CAPTURE_ADD_TO_QUEUE              => 'Adding capture request to queue',
        self::PAYMENT_CAPTURE_CREATE_TRANSACTION        => 'Create transaction on payment capture',
        self::PAYMENT_NOT_CAPTURED_CREATE_TRANSACTION   => 'Create transaction on payment failed capture',
        self::PAYMENT_CAPTURE_UPDATE_TRANSACTION        => 'Update existing transaction on payment capture',
        self::PAYMENT_CAPTURE_ORDER_UPDATE              => 'Update corresponding order on payment capture',
        self::PAYMENT_TRANSACTION_OLD                   => 'Updating/Creating transaction of an old payment',
        self::TRANSACTION_FREE_CREDITS                  => 'Using free credits for the payment',
        self::PAYMENT_VERIFY_CAPTURE_FAILURE            => 'Issue while performing verify for capture',
        self::VERIFY_CAPTURE_RESPONSE                   => 'Response received on verify capture',
        self::PAYMENT_ANALYTICS_UNRECOGNIZED_DATA       => 'Unrecognized data found in payment analytics log',
        self::PAYMENT_ANALYTICS_INCORRECT_DATA          => 'Incorrect data found in payment analytics log',
        self::VERIFY_REFUND_TRANSACTION_CREATED         => 'Refund transaction created in verify refund',
        self::MANUAL_GATEWAY_REFUND_RESPONSE            => 'Response received on manual gateway refund',
        self::MANUAL_GATEWAY_ALL_REFUNDS_RESPONSE       => 'Response received for all refunds on manual gateway refund',
        self::MANUAL_GATEWAY_REFUND_FAILURE             => 'Failed while trying to refund from gateway',
        self::MANUAL_GATEWAY_REFUND_INITIATED           => 'Manual gateway refund has been initiated for this refund id',
        self::REFUND_GATEWAY_REQUIRED                   => 'Traces whether the gateway refund is required or not',
        self::ORDER_MULTIPLE_CAPTURED_PAYMENTS          => 'Found more than one captured payment for an order.',
        self::PAYMENT_AUTO_REFUND_FAILURE               => 'Refund failed while trying to auto-refund',

        self::ADDRESS_PRIMARY_SWITCH                    => 'Switching primary address of an entity and address type',

        self::BAD_REQUEST_INVALID_API_KEY               => 'The api key provided is invalid',
        self::BAD_REQUEST_INVALID_API_SECRET            => 'The api secret provided is invalid',
        self::BAD_REQUEST_API_SECRET_NOT_PROVIDED       => 'API secret is not provided',

        self::PAYMENT_VERIFY_ALREADY_AUTHORIZED         => 'Payment being authorized is actually already authorized by some other thread',

        self::RUNTIME_ERROR                             => 'The request failed at runtime',

        self::GATEWAY_ENROLL_REQUEST                    => 'Request for enrollment sent',
        self::GATEWAY_ENROLL_RESPONSE                   => 'Enrollment response received',
        self::GATEWAY_ENROLL_ERROR                      => 'Error in enrollment',
        self::GATEWAY_NOT_ENROLLED_REQUEST              => 'Request for not-enrolled card sent',
        self::GATEWAY_NOT_ENROLLED_RESPONSE             => 'Response for not-enrolled card received',
        self::GATEWAY_NOT_ENROLLED_ERROR                => 'Error occured for not-enrolled card',
        self::GATEWAY_ENROLLED_AUTH_REQUEST             => 'Authentication request sent for enrolled card',
        self::GATEWAY_ENROLLED_AUTH_RESPONSE            => 'Authentication response received for enrolled card',
        self::GATEWAY_ENROLLED_AUTH_ERROR               => 'Authentication error occured for enrolled card',
        self::GATEWAY_SUPPORT_REQUEST                   => 'Support request sent',
        self::GATEWAY_SUPPORT_RESPONSE                  => 'Support response received',
        self::GATEWAY_SUPPORT_ERROR                     => 'Error in support',
        self::GATEWAY_UNKNOWN_ERROR                     => 'Unknown gateway error',
        self::GATEWAY_PAYMENT_VERIFY_UNEXPECTED         => 'Unexpected state of events in verify flow',
        self::GATEWAY_UNSUPPORTED_CARD_NETWORK          => 'Card network not supported',
        self::GATEWAY_VERIFY_INVALID_HEADER             => 'Gateway Verify invalid header',

        self::ERROR_EXCEPTION                           => 'Unhandled critical exception occured',
        self::RECOVERABLE_EXCEPTION                     => 'Recoverable exception occurred',
        self::MISC_TRACE_CODE                           => 'Miscellaneous trace code',
        self::ES_SAVE_FAILED                            => 'Failed while trying to save the entity to ES',
        self::ES_BULK_UPDATE_FAILED                     => 'Failed while bulk updating in ES',
        self::ES_BULK_UPDATE                            => 'Bulk update for ES',
        self::ES_SAVE_REQUEST                           => 'Request for saving in ES',

        self::RECON_ALERT                               => 'Alert raised for reconciliation',
        self::RECON_FILE_SKIP                           => 'Skipping a reconciliation file',
        self::RECON_MISMATCH                            => 'Mismatch between the data present in DB and recon file',
        self::RECON_PARSE_ERROR                         => 'Not able to parse some content of the recon file',
        self::RECON_FAILURE                             => 'Reconciliation could not happen',
        self::RECON_FAILED_VERIFY                       => 'Payment verify and authorize was unsuccessful',
        self::RECON_FILE_DELETE_FAILURE                 => 'Deleting local file during reconciliation',
        self::RECON_INFO_ALERT                          => 'Info alert raised for reconciliation',
        self::RECON_CRITICAL_ALERT                      => 'Critical alert raised for reconciliation',
        self::RECON_REQUEST                             => 'Request made for reconciliation',
        self::RECON_FILE_ROW                            => 'Row in the reconciliation file that is being reconciled',
        self::RECON_FILE_DETAILS                        => 'Details of all the files collected in the request',
        self::IIN_INSERT_FAILED                         => 'Inserting into Iin failed for given Iin',
        self::RECON_INFO                                => 'General recon info',
        self::REFUND_TRANSACTION_FAILED                 => 'Transaction failed to create for refund',
        self::RECON_INFO_SUMMARY                        => 'Summary of the reconciliation of the files',
        self::TRANSACTION_CREATED_IN_VERIFY_CAPTURE     => 'Transaction created for a failed capture',

        self::TRANSACTION_MIGRATION_TAX_MISTMATCH       => 'Mismatch in the tax calculation during migration',
        self::TRANSACTION_MIGRATION_FEE_MISTMATCH       => 'Mismatch in the fees calculation during migration',

        self::BATCH_UPLOAD_FILE_ENTRIES                 => 'Entries of the uploaded file',
        self::BATCH_UPLOAD_FILE                         => 'Uploaded batch file',
        self::BATCH_PROCESS_FILE                        => 'Processing the batch file',
        self::BATCH_ALREADY_PROCESSED                   => 'Batch File already processed',
        self::BATCH_PROCESSING_ERROR                    => 'Error in processing batch',
        self::BATCH_RETRY                               => 'Manual retry for the batch file',
        self::BATCH_RETRY_FAILURE                       => 'Failure in retrying batch file',
        self::BATCH_DOWNLOAD                            => 'Downloading the batch file',
        self::BATCH_LIST                                => 'Getting the batch files',
        self::BATCH_GET                                 => 'Get Batch by given id',
        self::BATCH_FILE_DELETE                         => 'Batch file delete',
        self::FEATURE_DELETE_REQUEST                    => 'Feature delete request initiated',
        self::FEATURE_MIGRATION_EXCEPTION               => 'Exception while creating features for merchant',
        self::FEATURE_ASSIGNMENT_EXCEPTION              => 'Exception assigning feature to merchant'
    );

    /**
     * Translate event code to message
     *
     * @param string $code event code
     * @return string
     */
    public static function getMessage($code)
    {
        if (isset(self::$messages[$code]) === false)
        {
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
