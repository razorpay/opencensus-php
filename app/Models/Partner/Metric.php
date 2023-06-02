<?php

namespace RZP\Models\Partner;

final class Metric
{
    //commission metrics
    const COMMISSION_CREATE_ATTEMPT_TOTAL        = 'commission_create_attempt_total';
    const COMMISSION_CREATED_TOTAL               = 'commission_created_total';
    const COMMISSION_CAPTURE_TOTAL               = 'commission_capture_total';
    const PAYMENT_COMMISSION_CREATED_TOTAL       = 'payment_commission_created_total';
    const PAYMENT_COMMISSION_FAILED_TOTAL        = 'payment_commission_failed_total';
    const PAYMENT_COMMISSION_CREATE_FAILED       = 'payment_commission_create_failed';

    const COMMISSION_FLUSH_TO_KAFKA_TOPIC_FAILED = 'commission_flush_to_kafka_topic_failed';
    const COMMISSION_FAILED_TOTAL = 'commission_failed_total';
    const COMMISSION_TRANSACTION_JOB_FAILED_TOTAL = 'commission_transaction_job_failed_total';
    const COMMISSION_TRANSACTION_JOB_EXHAUSTED_TOTAL = 'commission_transaction_job_exhausted_total';
    const COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FAILED_TOTAL = 'commission_transaction_on_hold_clear_failed_total';
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
    const COMMISSION_TDS_SETTLEMENT_PROCESS_TIME_MS     = "commission_tds_settlement_process_time_ms";
    const COMMISSION_TDS_SETTLEMENT_JOB_FAILURE_TOTAL   = "commission_tds_settlement_job_failure_total";
    const SUBMERCHANT_INVITE_BATCH_DAILY_LIMIT_EXCEEDED = 'submerchant_invite_batch_daily_limit_exceeded';
    const SUBMERCHANT_ONBOARDING_DAILY_LIMIT_EXCEEDED = 'submerchant_onboarding_daily_limit_exceeded';

    const PARTNER_ACTIVATION_AUTO_ACTIVATE_SUCCESS_TOTAL     = 'partner_activation_auto_activate_success_total';
    const PARTNER_ACTIVATION_AUTO_ACTIVATE_FAILURE_TOTAL     = 'partner_activation_auto_activate_failure_total';
    const COMMISSION_CAPTURE_JOB_PROCESSING_IN_MS            = 'commission_capture_job_processing_in_ms';
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

    const PARTNER_MIGRATION_AUDIT_JOB_FAILURE_TOTAL   = 'partner_migration_audit_job_failure_total';

    const SUBMERCHANT_FIRST_TRANSACTION_LATENCY_IN_MS = 'submerchant_first_transaction_latency_in_ms';

    const PARTNERSHIP_COMMISSION_SYNC_JOB_PUSH_SUCCESS    = 'partnership_commission_sync_job_push_success';
    const PARTNERSHIP_COMMISSION_SYNC_JOB_PUSH_FAILURE    = 'partnership_commission_sync_job_push_failure';
    const PARTNERSHIP_COMMISSION_CAPTURE_JOB_PUSH_SUCCESS = 'partnership_commission_capture_job_push_success';
    const PARTNERSHIP_COMMISSION_CAPTURE_JOB_PUSH_FAILURE = 'partnership_commission_capture_job_push_failure';


    const PARTNER_BULK_UPDATE_ONBOARDING_SOURCE_FAILURE = 'partner_bulk_update_onboarding_source_failure';


    const PARTNER_INVOICE_APPROVAL_AFTER_EXPIRY = 'partner_invoice_approval_after_expiry';

    const PARTNERS_KYC_STARTED_TOTAL = 'partners_kyc_started_total';
    const PARTNERS_KYC_SUBMITTED_TOTAL = 'partners_kyc_submitted_total';
    const PARTNERS_KYC_ACTIVATION_STATUS_TOTAL = 'partners_kyc_activation_status_total';
    const PARTNERS_ACTIVATED_TOTAL = 'partners_activated_total';
    const PARTNER_MARKED_SUB_MERCHANT_TOTAL = 'partner_marked_sub_merchant_total';

    const PARTNER_KYC_REQUEST_EMAIL_FAILED   = 'partner_kyc_request_email_failed';
    const PARTNER_KYC_REQUEST_SMS_FAILED   = 'partner_kyc_request_sms_failed';
}
