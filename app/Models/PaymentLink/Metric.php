<?php

namespace RZP\Models\PaymentLink;

/**
 * List of metrics in PaymentLink/ module
 */
final class Metric
{
    // Counters
    const PAYMENT_PAGE_PAID_TOTAL             = 'payment_page_paid_total';
    const PAYMENT_PAGE_EXPIRED_TOTAL          = 'payment_page_expired_total';
    const PAYMENT_PAGE_EMAIL_NOTIFY_TOTAL     = 'payment_page_email_notify_total';
    const PAYMENT_PAGE_SMS_NOTIFY_TOTAL       = 'payment_page_sms_notify_total';
    const PAYMENT_PAGE_PAYMENT_ATTEMPTS_TOTAL = 'payment_page_payment_attempts_total';
    const PAYMENT_PAGE_PAYMENT_REFUNDS_TOTAL  = 'payment_page_payment_refunds_total';
}
