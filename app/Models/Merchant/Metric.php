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
    const SUBMERCHANT_SUPPORT_ENTITIES_CREATION_FAILURE_TOTAL = 'submerchant_support_entities_creation_failure_total';

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

    const APPLY_GIFT_CARD_REQUEST_COUNT                        = 'apply_gift_card_request_count';
    const APPLY_GIFT_CARD_ERROR_COUNT                          = 'apply_gift_card_error_count';
    const APPLY_GIFT_CARD_REQUEST_DURATION_MILLIS              = 'apply_gift_card_request_duration_millis';

    const VALIDATE_GIFT_CARD_ERROR_COUNT                        = 'validate_gift_card_error_count';

    const MERCHANT_EXTERNAL_VALIDATE_GIFT_CARD_DURATION_MILLIS = self::MERCHANT_EXTERNAL_PREFIX . 'validate_gift_card_duration_millis';
    const MERCHANT_VALIDATE_GIFT_CARD_ERROR_COUNT              = 'merchant_validate_gift_card_error_count';


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
    const MERCHANT_SHIPPING_INFO_RESPONSE_COUNT = 'merchant_shipping_info_response_count';

    const ZIP_CODE_WITHOUT_ADDRESS_FOUND_COUNT          = 'zip_code_without_address_found_count';

    const MAGIC_COD_ENGINE_EVALUATE_API_ERROR_COUNT     = 'magic_cod_engine_evaluate_api_error_count';

    const WOOCOMMERCE_UPDATE_ORDER_STATUS_SUCCESS_COUNT     = 'woocommerce_update_order_status_success_count';
    const WOOCOMMERCE_UPDATE_ORDER_STATUS_ERROR_COUNT       = 'woocommerce_update_order_status_error_count';
    const WOOCOMMERCE_UPDATE_ORDER_STATUS_CALL_TIME         = 'woocommerce_update_order_status_call_time';

    const NATIVE_UPDATE_ORDER_STATUS_SUCCESS_COUNT      = 'native_update_order_status_success_count';
    const NATIVE_UPDATE_ORDER_STATUS_ERROR_COUNT        = 'native_update_order_status_error_count';
    const NATIVE_UPDATE_ORDER_STATUS_CALL_TIME          = 'native_update_order_status_call_time';

    const SHOPIFY_CANCEL_ORDER_SUCCESS_COUNT       = 'shopify_cancel_order_success_count';
    const SHOPIFY_CANCEL_ORDER_ERROR_COUNT         = 'shopify_cancel_order_error_count';
    const SHOPIFY_CANCEL_ORDER_CALL_TIME           = 'shopify_cancel_order_call_time';

    const SHOPIFY_CUSTOMER_SEARCH_REQUEST_COUNT       = 'shopify_customer_search_request_count';
    const SHOPIFY_CUSTOMER_SEARCH_ERROR_COUNT         = 'shopify_customer_search_error_count';
    const SHOPIFY_CUSTOMER_SEARCH_CALL_TIME           = 'shopify_customer_search_call_time';

    const SHOPIFY_GIFT_CARD_VALIDATE_REQUEST_COUNT  = 'shopify_gift_card_validate_request_count';
    const SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT    = 'shopify_gift_card_validate_error_count';
    const SHOPIFY_GIFT_CARD_VALIDATE_SUCCESS_COUNT  = 'shopify_gift_card_validate_success_count';

    const SHOPIFY_APPLY_GIFT_CARD_REQUEST_COUNT     = 'shopify_gift_card_validate_request_count';
    const SHOPIFY_APPLY_GIFT_CARD_ERROR_COUNT       = 'shopify_gift_card_validate_error_count';
    const SHOPIFY_APPLY_GIFT_CARD_SUCCESS_COUNT     = 'shopify_gift_card_validate_success_count';

    const SHOPIFY_ORDER_REFUND_REQUEST_COUNT        = 'shopify_order_refund_request_count';
    const SHOPIFY_ORDER_REFUND_SUCCESS_COUNT        = 'shopify_order_refund_success_count';
    const SHOPIFY_ORDER_REFUND_ERROR_COUNT          = 'shopify_order_refund_error_count';
    const SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_COUNT    = 'shopify_1cc_webhook_issue_refund_count';

    const SHOPIFY_GIFT_CARD_REFUND_REQUEST_COUNT    = 'shopify_gift_card_refund_request_count';
    const SHOPIFY_GIFT_CARD_REFUND_SUCCESS_COUNT    = 'shopify_gift_card_refund_success_count';
    const SHOPIFY_GIFT_CARD_REFUND_ERROR_COUNT      = 'shopify_gift_card_refund_error_count';

    const SHOPIFY_ADD_TAG_SUCCESS_COUNT             = 'shopify_add_tag_success_count';
    const SHOPIFY_ADD_TAG_ERROR_COUNT               = 'shopify_add_tag_status_error_count';
    const SHOPIFY_ADD_TAG_CALL_TIME                 = 'shopify_add_tag_call_time';

    const SHOPIFY_REMOVE_TAG_SUCCESS_COUNT          = 'shopify_remove_tag_success_count';
    const SHOPIFY_REMOVE_TAG_ERROR_COUNT            = 'shopify_remove_tag_status_error_count';
    const SHOPIFY_REMOVE_TAG_CALL_TIME              = 'shopify_remove_tag_call_time';

    const SHOPIFY_PARTIALLY_PAID_ORDER_COUNT         = 'shopify_partially_paid_order_count';
    const SHOPIFY_ORDER_CANCEL_REQUEST_COUNT         = 'shopify_order_cancel_request_count';
    const SHOPIFY_ADD_TAG_REQUEST_COUNT              = 'shopify_add_tag_request_count';

    const CREATE_API_CHECKOUT_ERROR_COUNT             = 'create_api_checkout_error_count';
    const CART_FETCH_API_ERROR_COUNT                  = 'cart_fetch_api_error_count';
    const SCRIPT_DISCOUNT_FETCH_FAIL_COUNT            = 'script_discount_fetch_fail_count';
    const SCRIPT_DISCOUNT_FETCH_SUCCESS_COUNT         = 'script_discount_fetch_success_count';

    const SHOPIFY_COUPON_FETCH_ERROR_COUNT              = 'shopify_coupon_fetch_error_count';
    const SHOPIFY_UPDATE_EMAIL_ERROR_COUNT              = 'shopify_update_email_error_count';
    const FETCH_SHIPPING_INFO_ERROR_COUNT               = 'fetch_shipping_info_error_count';

    const CREATE_SHOPIFY_CHECKOUT_REQUEST_COUNT         = 'create_shopify_checkout_request_count';
    const CREATE_SHOPIFY_CHECKOUT_ERROR_COUNT           = 'create_shopify_checkout_error_count';
    const CREATE_SHOPIFY_CHECKOUT_CALL_TIME             = 'create_shopify_checkout_call_time';

    const GET_SHOPIFY_CHECKOUT_DETAILS_REQUEST_COUNT    = 'get_shopify_checkout_details_request_count';
    const GET_SHOPIFY_CHECKOUT_DETAILS_ERROR_COUNT      = 'get_shopify_checkout_details_error_count';
    const GET_SHOPIFY_CHECKOUT_DETAILS_CALL_TIME        = 'get_shopify_checkout_details_call_time';

    const UPDATE_CHECKOUT_DETAILS_REQUEST_COUNT         = 'update_checkout_details_request_count';
    const UPDATE_CHECKOUT_DETAILS_ERROR_COUNT           = 'update_checkout_details_error_count';
    const UPDATE_CHECKOUT_DETAILS_CALL_TIME             = 'update_checkout_details_call_time';

    const ADD_MAGIC_URL_IN_CHECKOUT_REQUEST_COUNT       = 'add_magic_url_in_checkout_request_count';
    const ADD_MAGIC_URL_IN_CHECKOUT_ERROR_COUNT         = 'add_magic_url_in_checkout_error_count';
    const ADD_MAGIC_URL_IN_CHECKOUT_CALL_TIME           = 'add_magic_url_in_checkout_call_time';

    const PLACE_SHOPIFY_ORDER_REQUEST_COUNT             = 'place_shopify_order_request_count';
    const PLACE_SHOPIFY_ORDER_ERROR_COUNT               = 'place_shopify_order_error_count';
    const PLACE_SHOPIFY_ORDER_CALL_TIME                 = 'place_shopify_order_call_time';
    const SHOPIFY_1CC_PG_ROUTER_ERROR_COUNT             = 'shopify_1cc_pg_router_error_count';
    const SHOPIFY_1CC_ORDER_AMOUNT_EXCEEDS_ERROR_COUNT  = 'shopify_1cc_order_amount_exceeds_error_count';
    const SHOPIFY_1CC_API_RATE_LIMIT                    = 'shopify_1cc_api_rate_limit';

    const UPDATE_SHOPIFY_TRANSACTION_REQUEST_COUNT      = 'update_shopify_transaction_request_count';
    const UPDATE_SHOPIFY_TRANSACTION_ERROR_COUNT        = 'update_shopify_transaction_error_count';
    const UPDATE_SHOPIFY_TRANSACTION_CALL_TIME          = 'update_shopify_transaction_call_time';

    const ABANDON_CHECKOUT_ERROR_COUNT                  = 'abandon_checkout_error_count';

    const SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT         = 'shopify_complete_checkout_error_count';

    const SHOPIFY_ADD_CHECKOUT_URL_ERROR_COUNT          = 'shopify_add_checkout_url_error_count';

    const SHOPIFY_UPDATE_METAFIELD_SUCCESS_COUNT        = 'shopify_update_metafield_success_count';
    const SHOPIFY_UPDATE_METAFIELD_ERROR_COUNT          = 'shopify_update_metafield_error_count';

    const GET_CHECKOUT_BY_STOREFRONT_ID_REQUEST_COUNT   = 'get_checkout_by_storefront_id_request_count';
    const GET_CHECKOUT_BY_STOREFRONT_ID_CALL_TIME       = 'get_checkout_by_storefront_id_call_time';

    const GET_AVAILABLE_SHIPPING_RATES_REQUEST_COUNT    = 'get_available_shipping_rates_request_count';
    const GET_AVAILABLE_SHIPPING_RATES_CALL_TIME        = 'get_available_shipping_rates_call_time';

    const UPDATE_SHIPPING_ADDRESS_REQUEST_COUNT         = 'update_shipping_address_request_count';
    const UPDATE_SHIPPING_ADDRESS_CALL_TIME             = 'update_shipping_address_call_time';

    const RETRY_LIMIT_EXCEEDED_RATES_ERROR_COUNT        = 'retry_limit_exceeded_rates_error_count';

    const SHOPIFY_APPLY_COUPONS_REQUEST_COUNT           = 'shopify_apply_coupons_request_count';
    const SHOPIFY_APPLY_COUPONS_CALL_TIME               = 'shopify_apply_coupons_call_time';

    const SHOPIFY_REMOVE_COUPONS_REQUEST_COUNT          = 'shopify_remove_coupons_request_count';
    const SHOPIFY_REMOVE_COUPONS_CALL_TIME              = 'shopify_remove_coupons_call_time';

    const SHOPIFY_DISABLE_COUPON_ERROR_COUNT            = 'shopify_disable_coupon_error_count';
    const SHOPIFY_DISABLE_COUPON_CALL_TIME              = 'shopify_disable_coupon_call_time';
    const SHOPIFY_DISABLE_COUPON_SUCCESS_COUNT          = 'shopify_disable_coupon_success_count';

    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_TIME_MILLIS = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_duration_millis';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_failure_count';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_count';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_invalid_request_count';
    const MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_RESPONSE_COUNT = self::MERCHANT_EXTERNAL_PREFIX . 'shipping_info_call_invalid_response_count';
    const MERCHANT_SHIPPING_INFO_SHOPIFY_CALL_COUNT = 'shipping_info_shopify_call_count';
    const SHIPPING_SERVICE_CALL_COUNT = 'shipping_service_call_count';
    const SHIPPING_SERVICE_CALL_FAILURE_COUNT = 'shipping_service_call_failure_count';

    const AGGREGATE_SETTLEMENT_UNLINKING_REQUEST_SUCCESS = 'aggregate_settlement_unlinking_request_success';
    const AGGREGATE_SETTLEMENT_UNLINKING_REQUEST_FAILURE = 'aggregate_settlement_unlinking_request_failure';

    const AGGREGATOR_TO_RESELLER_MIGRATION_SUCCESS = 'aggregator_to_reseller_migration_success';
    const AGGREGATOR_TO_RESELLER_MIGRATION_FAILURE = 'aggregator_to_reseller_migration_failure';
    const RESELLER_TO_AGGREGATOR_MIGRATION_SUCCESS = 'reseller_to_aggregator_migration_success';
    const RESELLER_TO_AGGREGATOR_MIGRATION_FAILURE = 'reseller_to_aggregator_migration_failure';

    const RESELLER_TO_PURE_PLATFORM_MIGRATION_SUCCESS = 'reseller_to_pure_platform_migration_success';
    const RESELLER_TO_PURE_PLATFORM_MIGRATION_FAILURE = 'reseller_to_pure_platform_migration_failure';

    const COMMISSION_INVOICE_FINANCE_AUTO_APPROVED = 'commission_invoice_finance_auto_approved';
    const COMMISSION_INVOICE_FINANCE_UNDER_REVIEW  = 'commission_invoice_finance_under_review';
    const COMMISSION_INVOICE_FINANCE_AUTO_APPROVAL_FAILURE_TOTAL  = 'commission_invoice_finance_auto_approval_failure_total';
    const COMMISSION_INVOICE_AUTO_APPROVED = 'commission_invoice_auto_approved';

    const UPDATE_CUSTOMERS_DETAILS_REQUEST_COUNT = 'update_customers_details_request_count';
    const UPDATE_CUSTOMERS_DETAILS_ERROR_COUNT   = 'update_customers_details_error_count';
    const UPDATE_CUSTOMERS_DETAILS_REQUEST_FAULT_COUNT = 'update_customers_details_request_fault_count';
    const UPDATE_CUSTOMERS_DETAILS_TIME_MILLIS = 'update_customers_details_time_millis';

    const ONE_CC_VERIFY_OTP_REQUEST_COUNT = 'one_cc_verify_otp_request_count';
    const ONE_CC_VERIFY_OTP_REQUEST_FAULT_COUNT = 'one_cc_verify_otp_request_fault_count';
    const ONE_CC_VERIFY_OTP_REQUEST_ERROR_COUNT = 'one_cc_verify_otp_request_error_count';

    const AGG_SETTLEMENT_SUBM_MULTIPLE_PARTNER_LINK_REQUEST_SUCCESS_TOTAL   = 'agg_settlement_subm_multiple_partner_link_request_success';
    const AGG_SETTLEMENT_SUBM_MULTIPLE_PARTNER_LINK_REQUEST_FAILURE_TOTAL   = 'agg_settlement_subm_multiple_partner_link_request_failure';

    const COD_ELIGIBILITY_CALL_COUNT = 'cod_eligibility_call_count';
    const COD_ELIGIBILITY_CALL_ERROR_COUNT = 'cod_eligibility_call_error_count';

    const RESET_ORDER_REQUEST_COUNT = 'reset_order_request_count';
    const RESET_ORDER_ERROR_COUNT   = 'reset_order_error_count';
    const RESET_ORDER_TIME_MILLIS   = 'reset_order_time_millis';

    const WEBHOOK_PROCESS_REQUEST_1CC_COUNT                  = 'webhook_process_request_1cc_count';
    const WEBHOOK_PROCESS_REQUEST_1CC_ERROR_COUNT            = 'webhook_process_request_1cc_error_count';
    const SHOPIFY_WEBHOOK_JOB_FAILED_COUNT                   = 'shopify_webhook_job_failed_count';

    const SHOPIFY_1CC_SQS_JOB_EXCEEDED_MAX_RETRY_COUNT       = 'shopify_1cc_sqs_job_exceeded_max_retry_count';
    const SHOPIFY_1CC_SQS_JOB_RETRY_COUNT                    = 'shopify_1cc_sqs_job_retry_count';

    const SHOPIFY_1CC_APPLY_COUPON_ERROR_COUNT               = 'shopify_1cc_apply_coupon_error_count';
    const SHOPIFY_1CC_APPLY_COUPON_FAILURE_COUNT             = 'shopify_1cc_apply_coupon_failure_count';
    const SHOPIFY_1CC_UPDATE_EMAIL_FAILURE_COUNT             = 'shopify_1cc_update_email_failure_count';
    const SHOPIFY_1CC_ANALYTICS_COUNT                        = 'shopify_1cc_analytics_count';
    const SHOPIFY_1CC_RTO_LABEL_MISSING_COUNT              = 'shopify_1cc_rto_label_missing_count';

    // counters
    const BALANCE_FETCH_REQUEST_SYNC_CALL_WITHIN_RECENCY_THRESHOLD_COUNT = 'balance_fetch_request_sync_call_within_recency_threshold_count';
    const BALANCE_FETCH_REQUEST_SYNC_CALL_SUCCESSFUL_COUNT               = 'balance_fetch_request_sync_call_successful_count';
    const BALANCE_FETCH_REQUEST_SYNC_CALL_UNSUCCESSFUL_COUNT             = 'balance_fetch_request_sync_call_unsuccessful_count';
    // histogram
    const BALANCE_FETCH_REQUEST_SYNC_CALL_LATENCY = 'balance_fetch_request_sync_call_latency';
    const MAGIC_ANALYTICS_GET_CUSTOMER_INFO_COUNT            = 'magic_analytics_get_customer_info_count';

    const PARTNER_MIGRATION_REQUEST_CREATED = 'PARTNER_MIGRATION_REQUEST_CREATED';

    const FETCH_PAYMENT_PAGE_RECORDS_WITH_SEC_REF_ID = 'FETCH_PAYMENT_PAGE_RECORDS_WITH_SEC_REF_ID';

    const DATA_MISMATCH_FOR_WEBSITE_POLICY_AND_BMC_RESPONSE = 'DATA_MISMATCH_FOR_WEBSITE_POLICY_AND_BMC_RESPONSE';
}
