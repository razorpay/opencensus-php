<?php

namespace RZP\Models\Merchant\Detail;

/**
 * List of metrics in Merchant\Detail module
 */
final class Metric
{
    // ------------------------- Metrics -------------------------

    // Counters
    const POI_VERIFICATION_STATUS_TOTAL         = 'poi_verification_status_total';
    const POA_VERIFICATION_STATUS_TOTAL         = 'poa_verification_status_total';
    const COMPANY_PAN_VERIFICATION_STATUS_TOTAL = 'company_pan_verification_total';

    const PENNY_TESTING_STATUS_TOTAL              = 'penny_testing_status_total';
    const PENNY_TESTING_RETRY_COUNT               = 'penny_testing_retry_count';

    const EXTERNAL_VERIFIER_API_CALL_SUCCESS_TOTAL = 'external_verifier_api_call_success_total';
    const EXTERNAL_VERIFIER_API_CALL_FAILED_TOTAL  = 'external_verifier_api_call_failed_total';

    const MERCHANT_DOCUMENT_TYPE_SUBMITTED_TOTAL = 'merchant_document_type_submitted_total';
    const MERCHANT_DOCUMENT_OCR_PERFORMED_TOTAL  = 'merchant_document_ocr_performed_total';

    // Histograms
    const EXTERNAL_VERIFIER_API_CALL_DURATION_MS = 'external_verifier_api_call_duration_ms';
}
