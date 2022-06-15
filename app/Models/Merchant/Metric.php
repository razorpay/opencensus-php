<?php

namespace RZP\Models\Merchant;

final class Metric
{
    // ------------------------- Metrics -------------------------

    // ------ Counters ------

    /**
     * Method: Count
     * Dimensions: Partner_type
     */
    const PARTNER_MARKED_TOTAL                      = 'partner_marked_total';
    const PARTNER_MERCHANT_APPLICATION_CREATE_TOTAL = 'partner_merchant_application_create_total';
    const PARTNER_REFRESH_CLIENT_KEYS_TOTAL         = 'partner_refresh_client_keys_total';
    CONST PARTNER_REFRESH_CLIENT_KEYS_FAILURE       = 'partner_refresh_client_keys_failure';
    const PARTNER_CONFIG_CREATE_TOTAL               = 'partner_config_create_total';
    const PARTNER_MARK_REQUEST                      = 'partner_mark_request';
    const ADD_SUB_MERCHANT                          = 'add_sub_merchant';
    const SETTLE_TO_PARTNER_SUBMERCHANT_TOTAL       = 'settle_to_partner_submerchant_total';
    const SUB_MERCHANT_ADD_TYPE                     = 'sub_merchant_add_type';
    const SETTLE_TO_PARTNER_SUBMERCHANT_METRIC_PUSH_FAILURE       = 'settle_to_partner_submerchant_metric_push_failure';


    // General constants used for metrics
    const MARKETPLACE               = 'marketplace';
    const PARTNER                   = 'parnter';

    const COUPON_VALIDATE_TOTAL     = 'coupon_validate_total';
    const SIGNUP_COUPON_TOTAL       = 'signup_coupon_total';
    const SIGNUP_TOTAL              = 'signup_total';
    const PRE_EDIT_SIGNUP_TOTAL     = 'pre_edit_signup_total';

    const INTERNATIONAL_ACTIVATION  = 'international_activation';

    const UNREGISTERED_BUSINESS_DEFAULT_LIMIT_USED_TOTAL = 'unregistered_business_default_limit_used_total';


    const MERCHANT_ACTIVATION_STATE_TRANSITION = 'merchant_activation_state_transition';
    const MERCHANT_ACTIVATION                  = 'merchant_activation';
    const INTERNATIONAL_MERCHANT_ACTIVATION    = 'international_merchant_activation';

    //activation_flow_metrics constants
    const ACTIVATION_FLOW = 'activation_flow';
    const PREVIOUS_ACTIVATION_STATUS = 'previous_activation_status';
    const UPDATED_ACTIVATION_STATUS = 'updated_activation_status';

    const MERCHANT_RAZORPAYX_ACTIVATION_FAILED_TOTAL = 'merchant_razorpayx_activation_failed_total';

    const RAZORX_BULK_EVALUATE_TIME_MS = 'razorx_bulk_evaluate_time_ms';

    const MERCHANT_SUPPORT_ENTITIES_CREATION_FAILURE_TOTAL = 'merchant_support_entities_creation_failure_total';

    const SUBMERCHANT_TAGGING_FAILURE_TOTAL = 'submerchant_tagging_failure_total';

    const BUSINESS_BANKING_ENABLED_TRIGGER_FAILURE = 'business_banking_enabled_trigger_failure';

    const AFFILIATED_PARTNERS_FETCH_LATENCY = 'affiliated_partners_fetch_latency';
    const FETCH_ALL_SUBMERCHANTS_LATENCY = 'fetch_all_submerchants_latency';
    const FETCH_ALL_PARTNERS_LATENCY = 'fetch_all_partners_latency';

    const BATCH_UPLOAD_BY_ADMIN_TOTAL = 'batch_upload_by_admin_total';
    const BATCH_UPLOAD_BY_ADMIN_FAILURE_TOTAL = 'batch_upload_by_admin_failure_total';
    const BATCH_UPLOAD_BY_ADMIN_LATENCY = 'batch_upload_by_admin_latency';

    const SUBMERCHANT_BATCH_ACTION_SUCCESS_TOTAL = 'submerchant_batch_action_success_total';
    const SUBMERCHANT_BATCH_ACTION_FAILURE_TOTAL = 'submerchant_batch_action_failure_total';

    // 1CC metrics
    private const MERCHANT_EXTERNAL_PREFIX = 'merchant_external_';

    const MERCHANT_COUPONS_REQUEST_DURATION_MILLIS          = 'merchant_coupons_request_duration_millis';
    const MERCHANT_EXTERNAL_COUPONS_REQUEST_DURATION_MILLIS = self::MERCHANT_EXTERNAL_PREFIX . 'coupons_request_duration_millis';

    const FETCH_COUPONS_MERCHANT_REQUEST_COUNT              = 'fetch_coupons_merchant_request_count';
    const FETCH_COUPONS_MERCHANT_ERROR_COUNT                = 'fetch_coupons_merchant_error_count';
    const FETCH_COUPONS_REQUEST_COUNT                       = 'fetch_coupons_request_count';
    const FETCH_COUPONS_ERROR_COUNT                         = 'fetch_coupons_error_count';
    const FETCH_COUPONS_FAULT_COUNT                         = 'fetch_coupons_fault_count';

    const FETCH_COUPONS_SHOPIFY_REQUEST_COUNT              = 'fetch_coupons_shopify_request_count';

    const MERCHANT_COUPON_VALIDITY_REQUEST_DURATION_MILLIS                 = 'merchant_coupon_validity_request_duration_millis';
    const MERCHANT_COUPON_VALIDITY_REQUEST_COUNT                           = 'merchant_coupon_validity_check_request_count';
    const MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_TIME_MILLIS            = self::MERCHANT_EXTERNAL_PREFIX . 'coupon_validity_request_time_millis';
    const MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_COUNT  = self::MERCHANT_EXTERNAL_PREFIX . 'coupon_validity_request_count';
    const MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_INVALID_RESPONSE_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'coupon_validity_request_invalid_response_count';
    const MERCHANT_COUPON_VALIDITY_INVALID_REQUEST_COUNT  = 'merchant_coupon_validity_invalid_request_count';
    const MERCHANT_COUPON_VALIDITY_ERROR_COUNT  = 'merchant_coupon_validity_error_count';
    const MERCHANT_COUPON_VALIDITY_SHOPIFY_REQUEST_COUNT              = 'merchant_coupon_validity_shopify_request_count';

    // New settlements events cron
    const NSS_CRON_LAST_RUN_AT_SAME_VALUE                             = 'nss_cron_last_run_at_same_value'; 


    const MERCHANT_SHIPPING_INFO_CHECK_TIME_MILLIS = 'merchant_shipping_info_check_time_millis';
    const MERCHANT_SHIPPING_INFO_URL_UPDATE_FAILURE = 'merchant_shipping_info_url_update_failure';
    const MERCHANT_SHIPPING_INFO_CHECK_CALL_COUNT = 'merchant_shipping_info_check_call_count';
    const MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT = 'shipping_info_call_invalid_request_count';

    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_TIME_MILLIS = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_duration_millis';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_failure_count';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_count';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_invalid_request_count';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_RESPONSE_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_invalid_response_count';
    const MERCHANT_SHIPPING_INFO_SHOPIFY_CALL_COUNT = 'shipping_info_shopify_call_count';
    const SHIPPING_SERVICE_CALL_COUNT = 'shipping_service_call_count';
    const SHIPPING_SERVICE_CALL_FAILURE_COUNT = 'shipping_service_call_failure_count';

}
