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
    const CIN_VERIFICATION_STATUS_TOTAL         = 'cin_verification_status_total';
    const POA_VERIFICATION_STATUS_TOTAL         = 'poa_verification_status_total';
    const COMPANY_PAN_VERIFICATION_STATUS_TOTAL = 'company_pan_verification_total';
    const GSTIN_VERIFICATION_STATUS_TOTAL       = 'gstin_verification_status_total';

    const PENNY_TESTING_STATUS_TOTAL              = 'penny_testing_status_total';
    const PENNY_TESTING_RETRY_COUNT               = 'penny_testing_retry_count';

    const EXTERNAL_VERIFIER_API_CALL_SUCCESS_TOTAL = 'external_verifier_api_call_success_total';
    const EXTERNAL_VERIFIER_API_CALL_FAILED_TOTAL  = 'external_verifier_api_call_failed_total';

    const MERCHANT_DOCUMENT_TYPE_SUBMITTED_TOTAL = 'merchant_document_type_submitted_total';
    const MERCHANT_DOCUMENT_OCR_PERFORMED_TOTAL  = 'merchant_document_ocr_performed_total';

    const BVS_RESPONSE_TOTAL = 'bvs_response_total';
    const BVS_REQUEST_TOTAL  = 'bvs_request_total';

    // Histograms
    const EXTERNAL_VERIFIER_API_CALL_DURATION_MS = 'external_verifier_api_call_duration_ms';

    //BVS Valdiation Metrics
    const BVS_VALIDATION_STATUS_TOTAL         = 'bvs_validation_status_total';
    const VALIDATION_STATUS_BY_ARTEFACT_TOTAL = 'validation_status_by_artefact_total';
}
