<?php

namespace RZP\Models\UpiMandate;

class Metrics
{
    // Metric to be pushed in case a payment is going to fail due to any issue with pricing rules
    const UPI_AUTOPAY_PRICING_FAILED = 'upi_autopay_pricing_failed';

    // Metric to be pushed in case a payment is created via Promotional Intent flow
    const UPI_AUTOPAY_PROMOTIONAL_INTENT_PAYMENT_CREATED = 'upi_autopay_promotional_intent_payment_created';

    // Notification Metrics
    const UPI_AUTOPAY_NOTIFICATION_CREATED     = 'upi_autopay_notification_created';
    const UPI_AUTOPAY_NOTIFICATION_FAILED      = 'upi_autopay_notification_failed';
    const UPI_AUTOPAY_NOTIFICATION_DELIVERED   = 'upi_autopay_notification_delivered';
    const UPI_AUTOPAY_NOTIFICATION_PUSH_FAILED = 'upi_autopay_notification_push_failed';

    // UPI Mandate Metrics
    const UPI_AUTOPAY_MANDATE_CREATED   = 'upi_autopay_mandate_created';
    const UPI_AUTOPAY_MANDATE_CONFIRMED = 'upi_autopay_mandate_confirmed';
    const UPI_AUTOPAY_MANDATE_REJECTED  = 'upi_autopay_mandate_rejected';
    const UPI_AUTOPAY_MANDATE_REVOKED   = 'upi_autopay_mandate_revoked';
    const UPI_AUTOPAY_MANDATE_PAUSED    = 'upi_autopay_mandate_paused';
    const UPI_AUTOPAY_MANDATE_EXPIRED   = 'upi_autopay_mandate_expired';

    //Token Metrics
    const UPI_AUTOPAY_TOKEN_INITIATED = 'upi_autopay_token_initiated';
    const UPI_AUTOPAY_TOKEN_CONFIRMED = 'upi_autopay_token_confirmed';
    const UPI_AUTOPAY_TOKEN_CANCELLED = 'upi_autopay_token_cancelled';

    //Reminder Metrics
    const UPI_AUTOPAY_REMINDER_REQUEST_PDN    = 'upi_autopay_reminder_request_pdn';
    const UPI_AUTOPAY_REMINDER_RESPONSE_PDN   = 'upi_autopay_reminder_response_pdn';
    const UPI_AUTOPAY_REMINDER_REQUEST_DEBIT  = 'upi_autopay_reminder_request_debit';
    const UPI_AUTOPAY_REMINDER_RESPONSE_DEBIT = 'upi_autopay_reminder_response_debit';
}
