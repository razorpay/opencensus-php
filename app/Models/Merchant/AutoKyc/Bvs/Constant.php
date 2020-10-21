<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;

class Constant
{
    //
    // Request fields
    //
    const CONFIG_NAME       = 'config_name';
    const TYPE              = 'type';
    const DETAILS           = 'details';
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
    const LLPIN             = 'llpin';

    const BANK_ACCOUNT         = 'bank_account';
    const ACCOUNT_NUMBER       = 'account_number';
    const IFSC                 = 'ifsc';
    const ACCOUNT_HOLDER_NAMES = 'account_holder_names';

    // Config names
    const PERSONAL_PAN_OCR           = 'personal_pan_ocr';
    const BUSINESS_PAN_OCR           = 'business_pan_ocr';
    const CANCELLED_CHEQUE_OCR_REG   = 'cancelled_cheque_ocr_reg';
    const CANCELLED_CHEQUE_OCR_UNREG = 'cancelled_cheque_ocr_unreg';

    //
    // Response fields
    //
    const VALIDATION_ID   = 'validation_id';
    const VALIDATION_UNIT = 'validation_unit';
    const STATUS          = 'status';

    //
    // Artefact types in BVS
    //
    const PERSONAL_PAN = 'personal_pan';
    const AADHAAR      = 'aadhaar';
    const CIN          = 'cin';
    const GSTIN        = 'gstin';
    const VOTERS_ID    = 'voters_id';
    const PASSPORT     = 'passport';
    const LLP_DEED     = 'llp_deed';
    const BUSINESS_PAN = 'business_pan';

    const PG       = 'pg';
    const MERCHANT = 'merchant';

    const ARTEFACT_TYPE = 'artefact_type';

    const SUCCESS = 'success';
    const FAILURE = 'failure';

    const RAZORX_EXPERIMENT = 'razorx_experiment';

    const DOCUMENT_TYPE_ARTEFACT_DETAILS_MAP = [
        Type::AADHAR_FRONT     => [
            self::ARTEFACT_TYPE   => self::AADHAAR,
            self::PROOF_INDEX     => '3',
        ],
        Type::VOTER_ID_FRONT   => [
            self::ARTEFACT_TYPE   => self::VOTERS_ID,
            self::PROOF_INDEX     => '1',
        ],
        Type::PASSPORT_FRONT   => [
            self::ARTEFACT_TYPE   => self::PASSPORT,
            self::PROOF_INDEX     => '1',
        ],
        Type::PERSONAL_PAN     => [
            self::ARTEFACT_TYPE   => self::PERSONAL_PAN,
            self::PROOF_INDEX     => '1',
        ],
        Type::BUSINESS_PAN_URL => [
            self::ARTEFACT_TYPE   => self::BUSINESS_PAN,
            self::PROOF_INDEX     => '1',
        ],
        Type::CANCELLED_CHEQUE => [
            self::ARTEFACT_TYPE   => self::BANK_ACCOUNT,
            self::PROOF_INDEX     => '1',
        ],
    ];

    const ENABLE_VERIFICATION_AFTER_FORM_SUBMISSION = [
        Type::BUSINESS_PAN_URL => [
            self::RAZORX_EXPERIMENT => RazorxTreatment::BVS_BUSINESS_PAN_OCR,
        ],
        Type::PERSONAL_PAN     => [
            self::RAZORX_EXPERIMENT => RazorxTreatment::BVS_PERSONAL_PAN_OCR,
        ],
        Type::CANCELLED_CHEQUE => [
            self::RAZORX_EXPERIMENT => RazorxTreatment::BVS_CANCELLED_CHEQUE_OCR,
        ],
    ];
}
