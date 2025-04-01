<?php

namespace RZP\Models\Partner;

final class Metric
{
    // commission metrics
    const COMMISSION_CREATE_ATTEMPT_TOTAL        = 'commission_create_attempt_total';
    const COMMISSION_CREATED_TOTAL               = 'commission_created_total';
    const COMMISSION_CAPTURE_TOTAL               = 'commission_capture_total';
    const PAYMENT_COMMISSION_CREATED_TOTAL       = 'payment_commission_created_total';
    const PAYMENT_COMMISSION_FAILED_TOTAL        = 'payment_commission_failed_total';
    const PAYMENT_COMMISSION_CREATE_FAILED       = 'payment_commission_create_failed';

    const OAUTH_TRANSACTION_DEFAULT_PRICING_FETCH_FAILED     = 'oauth_transaction_default_pricing_fetch_failed';
    const OAUTH_TRANSACTION_CUSTOM_PRICING_FETCH_METRICS     = 'oauth_transaction_custom_pricing_fetch_metrics';

    const COMMISSION_FLUSH_TO_KAFKA_TOPIC_FAILED = 'commission_flush_to_kafka_topic_failed';
    const COMMISSION_FAILED_TOTAL = 'commission_failed_total';
    const COMMISSION_TRANSACTION_JOB_FAILED_TOTAL = 'commission_transaction_job_failed_total';
    const COMMISSION_TRANSACTION_JOB_EXHAUSTED_TOTAL = 'commission_transaction_job_exhausted_total';
    const COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FAILED_TOTAL = 'commission_transaction_on_hold_clear_failed_total';
    const COMMISSION_TRANSACTION_ON_HOLD_CLEAR_OLD_INVOICE_FAILED_TOTAL = 'commission_transaction_on_hold_clear_old_invoice_failed_total';
    const COMMISSION_INVOICE_GENERATE_JOB_FAILED_TOTAL = 'commission_invoice_generate_job_failed_total';
    const COMMISSION_INVOICE_GENERATE_RETRY_EXHAUSTED_TOTAL = 'commission_invoice_generate_retry_exhausted_total';
    const COMMISSION_TDS_SETTLEMENT_TOTAL = 'commission_tds_settlement_total';
    const COMMISSION_INVOICE_FINANCE_AUTO_APPROVAL_FAILURE_TOTAL  = 'commission_invoice_finance_auto_approval_failure_total';


    const SUBMERCHANT_CREATE_TOTAL              = 'submerchant_create_total';
    const SUBMERCHANT_USER_CREATE_TOTAL         = 'submerchant_user_create_total';
    const SUBMERCHANT_PRICING_PLAN_ASSIGN_TOTAL = 'submerchant_pricing_plan_assign_total';
    const PARTNER_ACTIVATION_CREATE_TOTAL       = 'partner_activation_create_total';
    const PARTNER_KYC_NOTIFICATION_TOTAL = 'partner_kyc_notification_total';

    const COMMISSION_ON_HOLD_CLEAR_PROCESS_TIME_MS      = "commission_on_hold_clear_process_time_ms";
    const COMMISSION_ON_HOLD_CLEAR_OLD_INVOICE_PROCESS_TIME_MS      = "commission_on_hold_clear_old_invoice_process_time_ms";
    const COMMISSION_TDS_SETTLEMENT_PROCESS_TIME_MS     = "commission_tds_settlement_process_time_ms";
    const COMMISSION_TDS_SETTLEMENT_JOB_FAILURE_TOTAL   = "commission_tds_settlement_job_failure_total";
    const SUBMERCHANT_INVITE_BATCH_DAILY_LIMIT_EXCEEDED = 'submerchant_invite_batch_daily_limit_exceeded';
    const SUBMERCHANT_ONBOARDING_DAILY_LIMIT_EXCEEDED = 'submerchant_onboarding_daily_limit_exceeded';

    const PARTNER_ACTIVATION_AUTO_ACTIVATE_SUCCESS_TOTAL     = 'partner_activation_auto_activate_success_total';
    const PARTNER_ACTIVATION_AUTO_UPDATE_FAILURE_TOTAL       = 'partner_activation_auto_update_failure_total';
    const COMMISSION_CAPTURE_JOB_PROCESSING_IN_MS            = 'commission_capture_job_processing_in_ms';
    const COMMISSION_REFUND_CREATE_JOB_PROCESSING_IN_MS      = 'commission_refund_create_job_processing_in_ms';
    const COMMISSION_INVOICE_GENERATION_JOB_PROCESSING_IN_MS = 'commission_invoice_generation_job_processing_in_ms';

    const COMMISSION_INVOICE_GENERATION_FAILED_TOTAL                = 'commission_invoice_generation_failed_total';
    const COMMISSION_INVOICE_SKIPPED_SUB_MTU_LIMIT                  = 'commission_invoice_skipped_sub_mtu_limit';
    const FETCH_PARTNER_SUB_MTU_COUNT_FAILED_TOTAL                  = 'fetch_partner_sub_mtu_count_failed_total';
    const FETCH_PARTNER_SUB_MTU_COUNT_QUERY_TIME                    = 'fetch_partner_sub_mtu_count_query_time';
    const COMMISSION_INVOICE_BULK_FETCH_SUCCESS_TOTAL               = 'commission_invoice_bulk_fetch_success_total';
    const COMMISSION_TRANSACTIONS_SETTLEMENTS_DISPATCH_FAILED_TOTAL = 'commission_transactions_settlements_dispatch_failed_total';

    const COMMISSION_ANALYTICS_FETCH = 'commission_analytics_fetch';
    const COMMISSION_FETCH           = 'commission_fetch';

    const PARTNER_CONFIG_ACTION_SUCCESS_TOTAL = 'partner_config_action_success_total';
    const PARTNER_CONFIG_BATCH_ACTION_SUCCESS_TOTAL = 'partner_config_batch_action_success_total';

    const PARTNER_SUB_MERCHANT_CONFIG_CREATE_TOTAL  = 'partner_sub_merchant_config_create_total';
    const PARTNER_SUB_MERCHANT_CONFIG_UPDATE_TOTAL  = 'partner_sub_merchant_config_update_total';
    const PARTNER_SUBMERCHANT_CONFIG_CREATE_FAILURE = 'partner_sub_merchant_config_create_failure';
    const PARTNER_SUBMERCHANT_CONFIG_UPDATE_FAILURE = 'partner_sub_merchant_config_update_failure';

    const PARTNER_CONFIG_AUDIT_LOGGER_JOB_FAILURE_TOTAL   = 'partner_config_audit_logger_job_failure_total';
    const PARTNER_CONFIG_AUDIT_LATENCY_IN_MS              = 'partner_config_audit_latency_in_ms';
    const PARTNER_CONFIG_AUDIT_SUCCESS                    = 'partner_config_audit_success';
    const PARTNER_CONFIG_AUDIT_FAIL                       = 'partner_config_audit_fail';
    const PARTNER_CONFIG_ENTITY_SYNC_FAILED               = 'partner_config_entity_sync_failed';
    const MERCHANT_APPLICATION_SYNC_FAILED                = 'merchant_application_sync_failed';
    const MERCHANT_ACCESS_MAP_SYNC_FAILED                 = 'merchant_access_map_sync_failed';


    const PARTNER_DOMAIN_BUILD_EVENT_PROPERTIES_FAILURE = 'partner_domain_build_event_properties_failure';

    const PARTNER_MIGRATION_AUDIT_JOB_FAILURE_TOTAL   = 'partner_migration_audit_job_failure_total';

    const SUBMERCHANT_FIRST_TRANSACTION_LATENCY_IN_MS = 'submerchant_first_transaction_latency_in_ms';

    const PRTS_COMMISSION_INVOICE_PUSH                = 'prts_commission_invoice_push';
    const PRTS_CREATE_SIGNUP_SOURCE_PUSH              = 'prts_create_signup_source_push';
    const PRTS_UPSERT_OAUTH_REFERRAL_LINK_PUSH        = 'prts_upsert_oauth_referral_link_push';
    const PRTS_ONBOARD_PARTNER_TO_LEDGER_PUSH         = 'prts_onboard_partner_to_ledger_push';

    // PRTS service metrics and dimensions
    const PRTS_COMMISSIONS_SHADOW_PHASE_EVENT_DISPATCH = 'prts_commissions_shadow_phase_event_dispatch';
    const PRTS_ACK_EVENT_DISPATCH                      = 'prts_ack_event_dispatch';


    const PARTNERSHIP_COMMISSION_CALCULATION   = 'partnership_commission_calculation';


    const PARTNER_BULK_UPDATE_ONBOARDING_SOURCE_FAILURE = 'partner_bulk_update_onboarding_source_failure';


    const PARTNER_INVOICE_APPROVAL_AFTER_EXPIRY = 'partner_invoice_approval_after_expiry';

    const PARTNERS_KYC_STARTED_TOTAL = 'partners_kyc_started_total';
    const PARTNERS_KYC_SUBMITTED_TOTAL = 'partners_kyc_submitted_total';
    const PARTNERS_KYC_ACTIVATION_STATUS_TOTAL = 'partners_kyc_activation_status_total';
    const PARTNERS_ACTIVATED_TOTAL = 'partners_activated_total';
    const PARTNER_MARKED_SUB_MERCHANT_TOTAL = 'partner_marked_sub_merchant_total';

    const PARTNER_KYC_REQUEST_EMAIL_FAILED   = 'partner_kyc_request_email_failed';
    const PARTNER_KYC_REQUEST_SMS_FAILED   = 'partner_kyc_request_sms_failed';

    const PARTNER_CALLBACK_EVENTS_RECEIVED_TOTAL = 'partner_callback_events_received_total';
    const PARTNER_CALLBACK_EVENTS_RECEIVED_FAILURE_TOTAL = 'partner_callback_events_received_failure_total';

    const PARTNER_CALLBACK_EVENTS_APP_NOT_FOUND_FAILURE_TOTAL = 'partner_callback_events_app_not_found_failure_total';

    const TRANSACTION_ISOLATION_SPLITZ_FAILURE = 'transaction_isolation_splitz_failure';
    const REVERSE_SHADOW_COMMISSION_CREATE_LAG   = 'reverse_shadow_commission_create_lag';
    const REVERSE_SHADOW_COMMISSION_INVOICE_CREATE_LAG = 'reverse_shadow_commission_INVOICE_create_lag';

    const MASK_PII_FIELDS_FAILED_TOTAL  = 'mask_pii_fields_failed_total';
    const MASK_PII_FIELDS_SUCCESS_TOTAL = 'mask_pii_fields_success_total';

    const UPDATE_PARTNER_PRICING_TEMPLATE = 'update_partner_pricing_template';

    const SUBMERCHANT_PAYMENT_RELEASE_SUCCESS = 'submerchant_payment_release_success';
    const SUBMERCHANT_PAYMENT_RELEASE_FAILURE = 'submerchant_payment_release_failure';

    const PARTNERSHIPS_DUAL_WRITE_REQUEST = 'partnerships_dual_write_request';

    const SWITCH_OVER_PARTNERSHIPS_EXPERIMENT = 'switch_over_partnerships_experiment';

    const SWITCH_OVER_PARTNERSHIPS_EXPERIMENT_FAILURE='switch_over_partnerships_experiment_failure';

    const SWITCH_OVER_PARTNERSHIPS_MERCHANT_APPLICATION_UNKNOWN_ENTITY='switch_over_partnerships_merchant_unknown_entity';
  
    const PARTNER_KYC_ACCESS_STATE_SYNC_SKIPPED  = 'partner_kyc_access_state_sync_skipped';

    const PARTNER_KYC_ACCESS_STATE_SYNC_TOTAL_REQUESTS = 'partner_kyc_access_state_sync_total_requests';
}
