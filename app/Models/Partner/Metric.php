<?php

namespace RZP\Models\Partner;

final class Metric
{
    const COMMISSION_CREATED_TOTAL = 'commission_created_total';
    const COMMISSION_CAPTURE_TOTAL = 'commission_capture_total';

    const SUBMERCHANT_CREATE_TOTAL              = 'submerchant_create_total';
    const SUBMERCHANT_USER_CREATE_TOTAL         = 'submerchant_user_create_total';
    const SUBMERCHANT_PRICING_PLAN_ASSIGN_TOTAL = 'submerchant_pricing_plan_assign_total';
    const PARTNER_ACTIVATION_CREATE_TOTAL       = 'partner_activation_create_total';

    const COMMISSION_ON_HOLD_CLEAR_PROCESS_TIME_MS      = "commission_on_hold_clear_process_time_ms";
    const COMMISSION_TDS_SETTLEMENT_PROCESS_TIME_MS     = "commission_tds_settlement_process_time_ms";
    const SUBMERCHANT_INVITE_BATCH_DAILY_LIMIT_EXCEEDED = 'submerchant_invite_batch_daily_limit_exceeded';

    const PARTNER_ACTIVATION_AUTO_ACTIVATE_SUCCESS_TOTAL     = 'partner_activation_auto_activate_success_total';
    const PARTNER_ACTIVATION_AUTO_ACTIVATE_FAILURE_TOTAL     = 'partner_activation_auto_activate_failure_total';
    const COMMISSION_CAPTURE_JOB_PROCESSING_IN_MS            = 'commission_capture_job_processing_in_ms';
    const COMMISSION_INVOICE_GENERATION_JOB_PROCESSING_IN_MS = 'commission_invoice_generation_job_processing_in_ms';

    const COMMISSION_INVOICE_GENERATION_FAILED_TOTAL                = 'commission_invoice_generation_failed_total';
    const COMMISSION_INVOICE_BULK_FETCH_SUCCESS_TOTAL               = 'commission_invoice_bulk_fetch_success_total';
    const COMMISSION_TRANSACTIONS_SETTLEMENTS_DISPATCH_FAILED_TOTAL = 'commission_transactions_settlements_dispatch_failed_total';

    const COMMISSION_ANALYTICS_FETCH = 'commission_analytics_fetch';
    const COMMISSION_FETCH           = 'commission_fetch';

    const PARTNER_CONFIG_ACTION_SUCCESS_TOTAL = 'partner_config_action_success_total';
    const PARTNER_CONFIG_BATCH_ACTION_SUCCESS_TOTAL = 'partner_config_batch_action_success_total';
}
