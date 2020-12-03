<?php

namespace RZP\Models\Invoice;

/**
 * List of metrics in Invoice/ module
 */
final class Metric
{
    // Counters
    const INVOICE_VIEW_TOTAL                    = 'invoice_view_total';
    const INVOICE_PAID_TOTAL                    = 'invoice_paid_total';
    const INVOICE_CREATED_TOTAL                 = 'invoice_created_total';
    const INVOICE_EXPIRED_TOTAL                 = 'invoice_expired_total';
    const INVOICE_DELETED_TOTAL                 = 'invoice_deleted_total';
    const INVOICE_SMS_NOTIFY_TOTAL              = 'invoice_sms_notify_total';
    const INVOICE_EMAIL_NOTIFY_TOTAL            = 'invoice_email_notify_total';
    const INVOICE_PAYMENT_ATTEMPTS_TOTAL        = 'invoice_payment_attempts_total';

    // Histograms
    const INVOICE_PDF_GEN_DURATION_MILLISECONDS = 'invoice_pdf_gen_duration_milliseconds';
}
