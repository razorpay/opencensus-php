<?php

namespace RZP\Models\Merchant\BvsValidation;

class Constants
{
    const PG      = 'pg';
    const CAPITAL = 'capital';

    const SUCCESS  = 'success';
    const FAILED   = 'failed';
    const CAPTURED = 'captured';

    //
    // message keys Kafka consumer
    //
    const VALIDATION_ID     = 'validation_id';
    const STATUS            = 'status';
    const ERROR_CODE        = 'error_code';
    const ERROR_DESCRIPTION = 'error_description';

    const BVS_KYC_VERIFICATION_RESULT       = 'bvs_kyc_verification_result';
    const MATCH                             = 'match';
    const MISMATCH                          = 'mismatch';
    const BVS_DOCUMENT_VERIFICATION_STATUS  = 'bvs_document_verification_status';

    //error codes
    const BVS_RULE_EXECUTION_ERROR          = 'RULE_EXECUTION_FAILED';

    const PLATFORMS = [
        self::PG,
        self::CAPITAL,
    ];

    const VALIDATION_STATUS = [
        self::SUCCESS,
        self::FAILED,
        self::CAPTURED,
    ];
}
