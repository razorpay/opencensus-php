<?php

namespace RZP\Models\Pricing\Calculator;

class Metrics
{
    // Metric for tracking the empty matched rules
    const EMPTY_MATCHED_RULES = 'pricing_empty_matched_rules';
    const SERVER_ERROR_PRICING_RULE_ABSENT_COUNT = 'server_error_pricing_rule_absent_count';
    const FALLBACK_PRICING_APPLIED_COUNT = 'fallback_pricing_applied_count';
    const CHARGE_COLLECTIONS_REQUEST_FAILURE = 'charge_collections_request_failure';
    const PRICING_ERROR_NO_RULE_FOUND_ON_FALLBACK_PRICING = 'pricing_error_no_rule_found_on_fallback_pricing';
}
