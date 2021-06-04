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
}
