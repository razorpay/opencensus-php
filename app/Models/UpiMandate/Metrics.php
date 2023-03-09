<?php

namespace RZP\Models\UpiMandate;

class Metrics
{
    // Metric to be pushed in case a payment is going to fail due to any issue with pricing rules
    const UPI_AUTOPAY_PRICING_FAILED = 'upi_autopay_pricing_failed';

    // Metric to be pushed in case a payment is created via Promotional Intent flow
    const UPI_AUTOPAY_PROMOTIONAL_INTENT_PAYMENT_CREATED = 'upi_autopay_promotional_intent_payment_created';
}
