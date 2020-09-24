<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Models\Merchant\Document\Type;

class Constant
{
    //
    // Request fields
    //
    const TYPE              = 'type';
    const DETAILS           = 'details';
    const IDENTIFIER        = 'identifier';
    const PAN_NUMBER        = 'pan_number';
    const NAME              = 'name';
    const ARTEFACT          = 'artefact';
    const ENRICHMENTS       = 'enrichments';
    const RULES             = 'rules';
    const PLATFORM          = 'platform';
    const OWNER_ID          = 'owner_id';
    const OWNER_TYPE        = 'owner_type';
    const PROOFS            = 'proofs';
    const NOTES             = 'notes';
    const UFH_FILE_ID       = 'ufh_file_id';
    const LEGAL_NAME        = 'legal_name';
    const TRADE_NAME        = 'trade_name';
    const COMPANY_NAME      = 'company_name';
    const LLP_NAME          = 'llp_name';
    const SIGNATORY_DETAILS = 'signatory_details';
    const FULL_NAME         = 'full_name';
    const PROOF_INDEX       = 'proof_index';

    //
    // Response fields
    //
    const VALIDATION_ID = 'validation_id';
    const STATUS        = 'status';

    //
    // Artefact types in BVS
    //
    const PERSONAL_PAN = 'personal_pan';
    const AADHAAR      = 'aadhaar';
    const CIN          = 'cin';
    const GSTIN        = 'gstin';
    const VOTER_ID     = 'voter_id';
    const PASSPORT     = 'passport';
    const LLPIN        = 'llpin';
    
    const PG       = 'pg';
    const MERCHANT = 'merchant';

    const ARTEFACT_TYPE = 'artefact_type';

    const SUCCESS = 'success';
    const FAILURE = 'failure';

    const DOCUMENT_TYPE_ARTEFACT_DETAILS_MAP = [
        Type::AADHAR_FRONT => [
            self::ARTEFACT_TYPE => self::AADHAAR,
            self::PROOF_INDEX   => '3',
        ],
        Type::VOTER_ID_FRONT => [
            self::ARTEFACT_TYPE => self::VOTER_ID,
            self::PROOF_INDEX   => '1',
        ],
        Type::PASSPORT_FRONT => [
            self::ARTEFACT_TYPE => self::PASSPORT,
            self::PROOF_INDEX   => '1',
        ],
    ];
}
