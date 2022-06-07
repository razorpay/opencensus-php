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
    const GSTIN_VERIFICATION_STATUS_TOTAL       = 'gstin_verification_status_total';

    const PENNY_TESTING_STATUS_TOTAL = 'penny_testing_status_total';
    const PENNY_TESTING_RETRY_COUNT  = 'penny_testing_retry_count';

    const EXTERNAL_VERIFIER_API_CALL_SUCCESS_TOTAL = 'external_verifier_api_call_success_total';
    const EXTERNAL_VERIFIER_API_CALL_FAILED_TOTAL  = 'external_verifier_api_call_failed_total';

    const MERCHANT_DOCUMENT_TYPE_SUBMITTED_TOTAL = 'merchant_document_type_submitted_total';
    const MERCHANT_DOCUMENT_OCR_PERFORMED_TOTAL  = 'merchant_document_ocr_performed_total';

    // Histograms
    const EXTERNAL_VERIFIER_API_CALL_DURATION_MS = 'external_verifier_api_call_duration_ms';

    //BVS Validation Metrics
    const BVS_VALIDATION_STATUS_TOTAL         = 'bvs_validation_status_total';
    const VALIDATION_STATUS_BY_ARTEFACT_TOTAL = 'validation_status_by_artefact_total';
    const BVS_VALIDATION_RETRY_ATTEMPT_TOTAL  = 'bvs_validation_retry_attempt_total';
    const BVS_RESPONSE_TOTAL                  = 'bvs_response_total';
    const BVS_REQUEST_TOTAL                   = 'bvs_request_total';
    const BVS_ARTEFACT_VERIFICATION_TRIGGER   = 'bvs_artefact_verification_trigger';

    const SHOP_ESTABLISHMENT_NUMBER_LENGTH_MORE_THAN_30 = 'shop_establishment_number_length_more_than_30';

    //BVS Company Search Metrics
    const BVS_COMPANY_SEARCH_RESPONSE_TOTAL  = 'bvs_company_search_response_total';
    const BVS_COMPANY_SEARCH_REQUEST_TOTAL   = 'bvs_company_search_request_total';
    const BVS_PROBE_API_FAILURE              = 'bvs_probe_api_failure';
    const COMPANY_SEARCH_EXHAUSTED           = 'company_search_exhausted';
    const REWARD_VALIDATION_EXHAUSTED        = 'reward_validation_exhausted';

    const BVS_GET_GST_DETAILS_REQUEST_TOTAL  = 'bvs_get_gst_details_request_total';
    const BVS_GET_GST_DETAILS_RESPONSE_TOTAL = 'bvs_get_gst_details_response_total';
    const GET_GST_DETAILS_EXHAUSTED          = 'get_gst_details_exhausted';

    const AUTOFILL_BVS_DETAILS_ATTEMPT_EXHAUSTED = 'autofill_bvs_details_attempt_exhausted';

}
