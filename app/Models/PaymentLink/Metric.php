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
    const PAYMENT_PAGE_PROCESSOR_RETRY_COUNT                      = 'PAYMENT_PAGE_PROCESSOR_RETRY_COUNT';

    // Payment Handle Metrics
    const PAYMENT_HANDLE_CREATION_TIME_TAKEN                      = 'payment_handle_creation_time_taken';
    const PAYMENT_HANDLE_CREATION_FAILED_COUNT                    = 'payment_handle_creation_failed_count';
    const PAYMENT_HANDLE_CREATION_SUCCESSFUL_COUNT                = 'payment_handle_creation_successful_count';
    const PAYMENT_HANDLE_CREATION_REQUEST                         = 'payment_handle_creation_request';

    const PAYMENT_HANDLE_VIEW_COUNT                               = 'payment_handle_view_count';

    const PAYMENT_HANDLE_UPDATE_TOTAL_REQUEST                     = 'payment_handle_update_total_request';
    const PAYMENT_HANDLE_UPDATE_TOTAL_SUCCESSFUL_REQUEST          = 'payment_handle_update_total_successful_request';

    const PAYMENT_HANDLE_AMOUNT_ENCRYPTION_TOTAL_REQUEST          = 'payment_handle_amount_encryption_total_request';

     const PAYMENT_HANDLE_PREVIEW_PAGE_VIEW_TOTAL                 = 'payment_handle_preview_page_view_total';

    const PAYMENT_HANDLE_SHORTENING_UNSUCCESSFUL_COUNT     = 'payment_handle_shortening_unsuccessful_count';
    const PAYMENT_HANDLE_SHORTENING_SUCCESSFUL_COUNT       = 'payment_handle_shortening_successful_count';

    const PAYMENT_HANDLE_DECRYPTION_SUCCESSFUL_TOTAL             = 'payment_handle_decryption_successful_total';
    const PAYMENT_HANDLE_DECRYPTION_UNSUCCESSFUL_TOTAL           = 'payment_handle_decryption_unsuccessful_total';

    // Gimli Caching Metrics
    const PAYMENT_PAGE_GIMLI_CACHE_HIT_COUNT    = 'PAYMENT_PAGE_GIMLI_CACHE_HIT_COUNT';
    const PAYMENT_PAGE_GIMLI_CACHE_MISS_COUNT   = 'PAYMENT_PAGE_GIMLI_CACHE_MISS_COUNT';

    // Hosted page cache metrics
    const PAYMENT_PAGE_HOSTED_CACHE_HIT_COUNT   = 'payment_page_hosted_cache_hit_count';
    const PAYMENT_PAGE_HOSTED_CACHE_MISS_COUNT  = 'payment_page_hosted_cache_miss_count';
    const PAYMENT_PAGE_HOSTED_CACHE_BUILD_COUNT = 'payment_page_hosted_cache_build_count';

    // custom url cache metrics
    const NOCODE_CUSTOM_URL_CACHE_HIT_COUNT         = 'nocode_custom_url_cache_hit_count';
    const NOCODE_CUSTOM_URL_CACHE_MISS_COUNT        = 'nocode_custom_url_cache_miss_count';
    const NOCODE_CUSTOM_URL_CACHE_BUILD_COUNT       = 'nocode_custom_url_cache_build_count';
    const NOCODE_CUSTOM_URL_CONSIDERED_COUNT        = 'nocode_custom_url_considered_count';
    const NOCODE_CUSTOM_URL_NOT_CONSIDERED_COUNT    = 'nocode_custom_url_not_considered_count';
    const NOCODE_CUSTOM_URL_CALLS_FAILED_COUNT      = 'nocode_custom_url_calls_failed_count';

    const PAYMENT_PAGE_IMAGE_UPLOAD_COUNT           = 'payment_page_image_upload_count';
    const PAYMENT_PAGE_IMAGE_COMPRESSION_HISTOGRAM  = 'payment_page_image_compression_histogram';

    // Cloudflare metrics
    const CF_REQUEST_COUNT                      = 'cf_request_count';
    const CF_REQUEST_LATENCY_MILLISECONDS       = 'cf_request_latency_milliseconds';
}
