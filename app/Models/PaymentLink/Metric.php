<?php

namespace RZP\Models\PaymentLink;

/**
 * List of metrics in PaymentLink/ module
 */
final class Metric
{
    // Counters
    const PAYMENT_PAGE_VIEW_TOTAL             = 'payment_page_view_total';
    const PAYMENT_PAGE_PAID_TOTAL             = 'payment_page_paid_total';
    const PAYMENT_PAGE_EXPIRED_TOTAL          = 'payment_page_expired_total';
    const PAYMENT_PAGE_EMAIL_NOTIFY_TOTAL     = 'payment_page_email_notify_total';
    const PAYMENT_PAGE_SMS_NOTIFY_TOTAL       = 'payment_page_sms_notify_total';
    const PAYMENT_PAGE_PAYMENT_ATTEMPTS_TOTAL = 'payment_page_payment_attempts_total';
    const PAYMENT_PAGE_PAYMENT_REFUNDS_TOTAL  = 'payment_page_payment_refunds_total';
    const PAYMENT_PAGE_CREATE_ORDER           = 'payment_page_create_order';
    const PAYMENT_PAGE_RECEIPT_GENERATED      = 'payment_page_receipt_generated';
    const PAYMENT_PAGE_SUBSCRIPTION_CREATED   = 'payment_page_subscription_created';
    const PAYMENT_PAGE_CREATED_TOTAL          = 'payment_page_created_total';
    const PAYMENT_PAGE_RISK_ALERT_COUNT       = 'payment_page_risk_alert_count';
    const PAYMENT_PAGE_EXPIRED_SEC            = 'payment_page_expired_sec';

    // Payment Page Processor Counters
    const PAYMENT_PAGE_PROCESSOR_COUNT_TOTAL                      = 'payment_page_processor_count_total';
    const PAYMENT_PAGE_PROCESSOR_TIME_TAKEN_TO_PICK_JOB           = 'payment_page_processor_time_taken_to_pick_job';
    const PAYMENT_PAGE_PROCESSOR_TOTAL_TIME_TO_COMPLETE_JOB       = 'payment_page_processor_total_time_to_complete_job';
    const PAYMENT_PAGE_PROCESSOR_TIME_TAKEN_TO_COMPLETE_TASK      = 'payment_page_processor_time_taken_to_complete_task';
    const PAYMENT_PAGE_PROCESSOR_JOB_SUCCESS_COUNT_TOTAL          = 'payment_page_processor_job_success_count_total';
    const PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL             = 'payment_page_processor_job_fail_count_total';

    // Payment Handle Metrics
    const PAYMENT_HANDLE_CREATION_TIME_TAKEN                      = 'payment_handle_creation_time_taken';
    const PAYMENT_HANDLE_CREATION_FAILED_COUNT                    = 'payment_handle_creation_failed_count';
    const PAYMENT_HANDLE_CREATION_SUCCESSFUL_COUNT                = 'payment_handle_creation_successful_count';
    const PAYMENT_HANDLE_CREATION_REQUEST                         = 'payment_handle_creation_request';

    const PAYMENT_HANDLE_SHORTENING_UNSUCCESSFUL_COUNT     = 'payment_handle_shortening_unsuccessful_count';
    const PAYMENT_HANDLE_SHORTENING_SUCCESSFUL_COUNT       = 'payment_handle_shortening_successful_count';
}
