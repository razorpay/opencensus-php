<?php

namespace RZP\Models\Merchant\BvsValidation;

class Constants
{
    const RX      = 'rx';
    const PG      = 'pg';
    const CAPITAL = 'capital';

    const SUCCESS  = 'success';
    const FAILED   = 'failed';
    const CAPTURED = 'captured';

    // validation units
    const IDENTIFIER = 'identifier';
    const PROOF      = 'proof';

    // message keys Kafka consumer
    const VALIDATION_ID         = 'validation_id';
    const STATUS                = 'status';
    const ERROR_CODE            = 'error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const RULE_EXECUTION_LIST   = 'rule_execution_list';
    const ENRICHMENT_DETAILS    = 'enrichmentDetails';
    const ERROR_REASON          = 'error_reason';

    const BVS_KYC_VERIFICATION_RESULT       = 'bvs_kyc_verification_result';
    const MATCH                             = 'match';
    const MISMATCH                          = 'mismatch';
    const BVS_DOCUMENT_VERIFICATION_STATUS  = 'bvs_document_verification_status';
    const DOCUMENT_VERIFICATION_STATUS_KEY  = 'document_verification_status_key';
    const RETRY_ATTEMPT_COUNT               = 'retry_attempt_count';
    const RESPONSE_TIME_MILLI_SECONDS       = 'response_time_milli_seconds';
    const BANK_DETAILS_VERIFICATION_ERROR   = 'bank_details_verification_error';
    const BANK_DETAILS_FUZZY_SCORE          = 'bank_details_fuzzy_score';

    const ID                    = 'id';
    const COUNT                 = 'count';
    const DOCUMENTS_DETAIL      = 'documents_details';
    const TYPE                  = 'type';
    const UFH_FILE_ID           = 'ufh_file_id';
    const ACCEPTANCE_TIMESTAMP  = 'acceptance_timestamp';
    const OWNER_ID              = 'owner_id';
    const CLIENT_ID             = 'client_id';
    const PLATFORM              = 'platform';

    const API_RETRY_JOB_FAILURE = 'api_retry_job_failure';

    const PLATFORMS = [
        self::RX,
        self::PG,
        self::CAPITAL,
    ];

    const VALIDATION_STATUS = [
        self::SUCCESS,
        self::FAILED,
        self::CAPTURED
    ];

    const VALIDATION_UNIT = [
        self::IDENTIFIER,
        self::PROOF,
    ];


    // Validation status
    const VERIFIED          = 'verified';
    const INCORRECT_DETAILS = 'incorrect_details';
    const NOT_MATCHED       = 'not_matched';
    const PENDING           = 'pending';
    const INITIATED         = 'initiated';
    const NOT_INITIATED     = 'not_initiated';
    const FAILED_STATUS = [
        self::FAILED,
        self::INCORRECT_DETAILS,
        self::NOT_MATCHED
    ];

    // error codes
    const INPUT_DATA_ISSUE      = 'INPUT_DATA_ISSUE';
    const DATA_UNAVAILABLE      = 'DATA_UNAVAILABLE';
    const RULE_EXECUTION_FAILED = 'RULE_EXECUTION_FAILED';
    const SPAM_DETECTED_ERROR   = 'SPAM_DETECTED_ERROR';

    const ERROR_MAPPING = [
        self::FAILED            => [
            'NO_PROVIDER_ERROR',
            'RETRIES_EXCEEDED',
            'PROCESSOR_ERROR',
            'DATA_CROSS_CHECK_ERROR',
            'EXTERNAL_SERVICE_ERROR',
            'INTERNAL_ERROR',
        ],
        self::INCORRECT_DETAILS => [
            'VALIDATION_ERROR',
            'ARTEFACT_INVALIDATED',
            'DOCUMENT_UNIDENTIFIABLE',
            'INPUT_DATA_ISSUE',
            'INPUT_IMAGE_ISSUE',
            'DATA_UNAVAILABLE',
            'REMOTE_RECORDS_INCONSISTENT',
            'DATA_CROSS_CHECK_ERROR',
            'NOT_SUPPORTED',
            'INVALID_DOCUMENT_ERROR'
        ],
        self::NOT_MATCHED => [
            'RULE_EXECUTION_FAILED'
        ]
    ];

    const BANK_ACCOUNT = 'bank_account';

    const RESULT       = 'result';
}
