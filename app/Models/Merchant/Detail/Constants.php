<?php

namespace RZP\Models\Merchant\Detail;

use Razorpay\IFSC\Bank;

class Constants
{
    // input params for pan verifier
    const PAN_NUMBER        = 'pan_number';
    const PROMOTER_PAN      = 'promoter_pan';
    const PROMOTER_PAN_NAME = 'promoter_pan_name';
    const MEMBERS           = 'members';

    // input for business pan verifier
    const COMPANY_PAN      = 'company_pan';
    const BUSINESS_PAN     = 'business_pan';
    const COMPANY_PAN_NAME = 'company_pan_name';

    const POI_STATUS                       = 'poi_status';
    const POA_STATUS                       = 'poa_status';
    const GSTIN_STATUS                     = 'gstin_status';
    const DOCUMENT_TYPE                    = 'document_type';
    const DOCUMENT_SOURCE                  = 'document_source';
    const BANK_DETAILS_VERIFICATION_STATUS = 'bank_details_verification_status';
    const COMPANY_PAN_VERIFICATION_STATUS  = 'company_pan_verification_status';
    const EXTERNAL_VERIFIER                = 'external_verifier';
    const BUSINESS_TYPE                    = 'business_type';

    // pan verifier response types
    const INCORRECT_DETAILS = 'incorrect_details';
    const SUCCESS           = 'success';
    const FAILURE           = 'failure';

    const SIGNED_URL             = 'signed_url';
    const PASSPORT_FRONT         = 'passport_front';
    const AADHAR_FRONT           = 'aadhar_front';
    const VOTER_ID_FRONT         = 'voter_id_front';
    const AADHAAR_FRONT_COMPLETE = 'aadhaar_front_complete';

    // Email constants
    const MERCHANT             = 'merchant';
    const ORG                  = 'org';
    const HOSTNAME             = 'hostname';
    const CLARIFICATION_REASON = 'clarification_reason';

    // penny testing constants
    const MERCHANT_ID                                   = 'merchant_id';
    const ACCOUNT_STATUS                                = 'account_status';
    const REGISTERED_NAME                               = 'registered_name';
    const PENNY_TESTING_FUZZY_MATCH_PERCENTAGE_WITH_PAN = 'fuzzy_match_percentage_with_pan';
    const PENNY_TESTING_FUZZY_MATCH_BASE                = 'penny_testing_fuzzy_match_base';
    const PENNY_TESTING_FUZZY_MATCH_TYPE_FOR_PAN        = 'penny_testing_fuzzy_match_type_for_pan';
    const POA_FUZZY_MATCH_TYPE                          = 'poa_fuzzy_match_type';
    const POI_FUZZY_MATCH_TYPE                          = 'poi_fuzzy_match_type';
    const BANK_VERIFICATION_THRESHOLD_FOR_PAN           = 'bank_detail_verification_threshold_for_pan';
    const IS_VALID_NAME                                 = 'is_valid_name';
    const PENNY_TESTING_ATTEMPT_COUNT_REDIS_KEY_PREFIX  = 'penny_testing_attempt_count';
    const PENNY_TESTING_ATTEMPT_COUNT_TTL_IN_SEC        = 120;
    const PENNY_TESTING_MAX_ATTEMPT                     = 2;
    const PENNY_TESTING_RETRY_PERIOD_IN_SEC             = 7200;
    const UNREGISTERED                                  = 'unregistered';

    // merchant verification
    const VERIFICATION    = 'verification';
    const REQUIRED_FIELDS = 'required_fields';

    const DUMMY_ACTIVATION_FILE = '100000000Dummy';

    //kyc integration constants
    const ENTITY_ID              = 'entity_id';
    const KYC_ID                 = 'kyc_id';
    const STATUS_CODE            = 'status_code';
    const PAN_NAME_FROM_NSDL     = 'pan_name_from_nsdl';
    const INTERNAL_ERROR         = 'internal_error';
    const INTERNAL_ERROR_CODE    = 'internal_error_code';
    const INTERNAL_ERROR_MESSAGE = 'internal_error_message';
    const CODE                   = 'code';
    const MESSAGE                = 'message';
    const NAME                   = 'name';
    const DOCUMENTS              = 'documents';
    const CONTEXT                = 'context';
    const METHOD                 = 'method';
    const OCR_RESPONSE           = 'ocr_response';
    const VERIFICATION_RESULT    = 'verification_result';

    //gstin integration constants
    const COMPANY_NAME        = 'company_name';
    const LEGAL_NAME          = 'legal_name';
    const OPERATIONAL_ADDRESS = 'operational_address';
    const ADDRESS             = 'address';

    const DOCUMENT_VERIFICATION_STATUS          = 'document_verification_status';
    const OCR_MATCHING_PERCENTAGE_WITH_PAN_NAME = 'ocr_match_percentage_with_pan_name';

    const DOCUMENT_TYPES = [
        self::PERSONAL_PAN    => 'personal_pan',
        self::BUSINESS_PAN    => 'business_pan',
        self::AADHAAR         => 'aadhaar',
        self::PASSPORT        => 'passport',
        self::VOTERS_ID       => 'voters_id',
        self::DRIVERS_LICENSE => 'drivers_license',
        self::GSTIN           => 'gstin',
    ];

    // Using this blacked listed banks to block bank account details
    // update in merchant details
    const BLACKLISTED_BANKS = [
    ];

    const KYC_API_TYPES = ['AUTH' => 'auth', 'OCR' => 'ocr'];

    const DOCUMENT_FILE_ID = 'document_file_id';

    // kyc service error codes
    const  VALIDATION_ERROR = 'VALIDATION_ERROR';
    const  NO_DATA_FOUND    = 'NO_DATA_FOUND';
    const  BAD_REQUEST      = 'BAD_REQUEST';
    const  UNAUTHORIZED     = 'UNAUTHORIZED';


    // kyc service processor type
    const PROCESSOR_TYPE = "processorType";
    const POI            = 'POI';
    const POA            = 'POA';
    const REGISTER       = 'REGISTER';

    // kyc service document type
    const PERSONAL_PAN    = 'PERSONAL_PAN';
    const AADHAAR         = 'AADHAAR';
    const PASSPORT        = 'PASSPORT';
    const VOTERS_ID       = 'VOTERS_ID';
    const DRIVERS_LICENSE = 'DRIVERS_LICENSE';
    const GSTIN           = 'GSTIN';

    // flows used during activation
    const ACTIVATION               = 'activaiton';
    const INTERNATIONAL_ACTIVATION = 'international_activation';

    const ACTIVATION_FLOWS = [self::ACTIVATION, self::INTERNATIONAL_ACTIVATION];


    // Kyc Events constant
    const RESPONSE_BODY             = 'response_body';
    const RESPONSE_TIME             = 'response_time';
    const API_STATUS_CODE           = 'api_status_code';
    const VERIFIED                  = 'verified';
    const VERIFICATION_THRESHOLD    = 'verification_threshold';
    const API_CALL_SUCCESSFUL       = 'api_call_successful';
    const API_ERROR_CODE            = 'api_error_code';
    const VERIFICATION_STATUS       = 'verification_status';
    const COMPARISION               = 'comparisons';
    const MATCH_PERCENTAGE          = 'match_percentage';
    const MATCH_THRESHOLD           = 'match_threshold';
    const DETAILS_FROM_API_RESPONSE = 'detail_from_api';
    const DETAILS_FROM_USER         = 'detail_from_user';
    const MATCH_TYPE                = 'match_type';

}

