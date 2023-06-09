<?php

namespace RZP\Models\Payment\Processor;

class Constants
{
    const REQUEST_TYPE                  = 'request_type';

    const REQUEST_TYPE_OTP              = 'request_type_otp';

    const REQUEST_TYPE_REDIRECT         = 'request_type_redirect';

    const ACTION                        = 'action';

    const ACTION_OTP_RESEND             = 'otp_resend';

    const UPI                           = 'upi';

    const CARD                          = 'card';

    const EMANDATE                      = 'emandate';

    const CREATED                       = 'created';

    const SUBSCRIPTION                  = 'subscription';

    const ADMIN_EMAIL                   = 'admin_email';
    const USER_EMAIL                    = 'user_email';
    const META_DATA                     = 'meta_data';
    const INITIATOR_EMAIL_ID            = 'initiator_email_id';
    const IS_CRON                       = 'is_cron';
    const IS_DASHBOARD_APP              = 'is_dashboard_app';
    const ROUTE_NAME                    = 'route_name';
    const IS_BATCH                      = 'is_batch';
    const CREATOR_ID                    = 'creator_id';
    const CREATOR_TYPE                  = 'creator_type';
    const IS_PAYMENT_CAPTURED           = 'is_payment_captured';
    const IS_ADMIN_AUTH                 = 'is_admin_auth';
    const IS_PAYMENT_AMOUNT_MISMATCH    = 'is_payment_amount_mismatch';
    const DIRECT_SETTLEMENT_WITH_REFUND = 'direct_settlement_with_refund';
    const DIRECT_SETTLEMENT_WITHOUT_REFUND = 'direct_settlement_without_refund';
    const PAYMENT_SETTLED_BY            = 'payment_settled_by';
    const PAYMENT_REFERENCE1            = 'payment_reference1';
    const FORCE_AUTH_PAYMENT            = 'force_auth_payment';
    const AUTHENTICATION_CHANNEL        = 'authentication_channel';
    const DEFAULT_AUTHENTICATION_CHANNEL        = 'browser';

    //kafka constants
    const REGISTER_PAYMENT_IN_SCHEDULER                = 'register_payment_in_scheduler';
    const DEREGISTER_PAYMENT_IN_SCHEDULER              = 'deregister_payment_in_scheduler';
    const KAFKA_MESSAGE_TASK_NAME                      = 'task_name';
    const KAFKA_MESSAGE_DATA                           = 'data';
    const NAMESPACE                                    = 'namespace';
    const ENTITY_ID                                    = 'entity_id';
    const ENTITY_TYPE                                  = 'entity_type';
    const PAYMENT_ID                                   = 'payment_id';
    const REMINDER_DATA                                = 'reminder_data';
    const VERIFY_AT                                    = 'verify_at';
    const TIMEOUT_AT                                   = 'timeout_at';
    const VERIFY_SERVICE                               = 'verify_service';
    const TIMEOUT_SERVICE                              = 'timeout_service';
    const ACTIVE                                       = 'active';

    const NOTHING_VIA_SCHEDULER                 =   null;
    const VERIFY_VIA_SCHEDULER                  =   1;
    const TIMEOUT_VIA_SCHEDULER                 =   2;
    const VERIFY_AND_TIMEOUT_VIA_SCHEDULER      =   3;

    const VALID_FOR_TIMEOUT_DEREGISTRATION      =   array(self::TIMEOUT_VIA_SCHEDULER, self::VERIFY_AND_TIMEOUT_VIA_SCHEDULER);

    //Namespace suffix
    const TIMEOUT_SUFFIX       =   "_payments_timeout";

    //Auto Refund Reasons
    const MERCHANT_AUTO_REFUND_DELAY            = 'Merchants auto_refund_delay %s has been set as refund_at value';
    const REFUND_AT_FOR_EMANDATE_PAYMENT        = 'Merchants default value %s for emandate payments has been set as refund_at value';
    const REFUND_AT_FOR_NACH_PAYMENT            = 'Merchants default value %s for nach payments has been set as refund_at value';
    const REFUND_AT_FOR_UPI_OTM                 = 'Merchants auto_refund_delay %s has been set as refund_at value to UPI metadata end time';
    const REFUND_AT_OVERRIDDEN_CAPTURE_SETTINGS = 'Payment refund at value has been overridden with capture settings value, %s';
    const MERCHANT_AUTO_REFUND_DELAY_FOR_NETBANKING_CORPORATE = 'Merchant has enabled the nb_corporate_delay_refund flag option, there will be a %s delay in refunding payments';

    //Auto Capture Reasons
    const UPI_OTM_PAYMENT                           = 'UPI OTM Payment';
    const BANK_TRANSFER_PAYMENT                     = 'Bank Transfer Payment';
    const PAYMENT_STATUS_AUTHENTICATED              = 'Payment Status Authenticated';
    const INTL_BANK_TRANSFER_PAYMENT                = 'International Bank Transfer Payment';
    const UPI_TRANSFER_PAYMENT                      = 'UPI Transfer Payment';
    const PAYMENT_LINK_WITH_FEATURE                 = 'Payment Link with Merchant Feature PAYMENT_PAGES_NO_CAPTURE enabled';
    const PAYMENT_STATUS_NOT_AUTHORIZED             = 'Payment should be in authorized status.';
    const DIRECT_SETTLEMENT_PAYMENT                 = 'Direct Settlement Payment without order.';
    const AUTH_SPLIT_FEATURE_ENABLED                = 'Merchant has auth_split feature enabled.';
    const PAYMENT_ATTRIBUTE_CAPTURE_TRUE            = 'Capture Attribute in Payment is true.';
    const PAYMENT_HAS_NO_ORDER                      = 'Payment does not have an order associated with it.';
    const SUBSCRIPTION_PAYMENT                      = 'Subscription Payment.';
    const FILE_BASED_EMANDATE_PAYMENT               = 'File based emandate Payment.';
    const API_BASED_EMANDATE_ASYNC_PAYMENT          = 'API based emandate async payment.';
    const POS_PAYMENT                               = 'Pos Payment.';
    const DIRECT_SETTLEMENT_ORDER_NOT_PAID          = 'Direct Settlement Payment with order is still not paid.';
    const ORDER_ALREADY_MARKED_PAID                 = 'Order already marked as paid.';
    const PAYMENT_AMOUNT_GREATER_THAN_AMOUNT_DUE    = 'Payment amount is greater than amount due.';
    const CAPTURE_SETTINGS_AUTOMATIC                = 'Capture settings is automatic.';
    const CAPTURE_SETTINGS_MANUAL                   = 'Capture settings is manual.';
    const ORDER_PAYMENT_CAPTURE_NOT_TRUE            = 'Order payment capture flag is not true.';
    const AUTO_REFUND_DELAY_EXCEEDED                = 'Auto Refund delay exceeded for this payment.';
    const PAYMENT_PASSED_ALL_CHECKS_FOR_CAPTURE     = 'Payment passed all checks for auto capture.';
    const MERCHANT_AUTO_CAPTURE_LATE_AUTH_TRUE      = 'Merchant has auto capture late auth enabled.';
    const MERCHANT_AUTO_CAPTURE_LATE_AUTH_FALSE     = 'Merchant has auto capture late auth disabled.';
    const PAYMENT_METHOD_COD                        = 'Cannot auto capture cash on delivery payment.';
    const ORDER_PAYMENT_CAPTURE_TRUE                = 'Order payment capture flag is true.';
    const OPTIMIZER_AUTO_CAPTURE_TIMEOUT_EXCEEDED   = 'Optimizer Auto Capture timeout exceeded';

    // default time out for upi and card subsequent payment
    const AUTO_CAPTURE_DEFAULT_TIMEOUT_UPI_RECURRING_AUTO = 2160;
    
    const AUTO_CAPTURE_TIMEOUT_FOR_UPI_RECURRING_AUTO_DEBIT_RETRIES = 4320;

    const AUTO_CAPTURE_DEFAULT_TIMEOUT_CARD_RECURRING_AUTO = 4320;

    const OPGSP_TRANSACTION_LIMIT_USD = 200000;

    // optimizer
    const OPTIMIZER_GATEWAY_DATA = 'optimizer_gateway_data';
    const DATA                   = 'data';

}
