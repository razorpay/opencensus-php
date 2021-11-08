<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;

class Constant
{
    //
    // Request fields
    //
    const BUSINESS_NAME     = 'business_name';
    const NAME_OF_PARTNERS   = 'name_of_partners';
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
    const SIGNATORY_NAME    = 'signatory_name';
    const LLP_NAME          = 'llp_name';
    const SIGNATORY_DETAILS = 'signatory_details';
    const FULL_NAME         = 'full_name';
    const PROOF_INDEX       = 'proof_index';
    const LLPIN             = 'llpin';
    const PROBE_ID          = 'probe_id';
    const IP                = 'ip';
    const USER_AGENT        = 'user_agent';
    const ACTOR             = 'actor';
    const SOURCE            = 'source';
    const FLOW              = 'flow';
    const ACTOR_ID          = 'id';
    const ACTOR_ROLE        = 'role';
    const ACTOR_EMAIL       = 'email';
    const META_DATA         = 'meta_data';

    const ENRICHMENT_DETAIL_FIELDS = "enrichment_details_fields";

    const BANK_ACCOUNT             = 'bank_account';
    const ACCOUNT_NUMBER           = 'account_number';
    const IFSC                     = 'ifsc';
    const BENEFICIARY_NAME         = 'beneficiary_name';
    const ACCOUNT_HOLDER_NAMES     = 'account_holder_names';
    const SHOP_REGISTRATION_NUMBER = 'registration_number';
    const SHOP_AREA_CODE           = 'area_code';
    const SHOP_ENTITY_NAME         = 'entity_name';
    const SHOP_OWNER_NAME          = 'owner_name';

    // CreateValidation Flows
    const ONBOARDING_FLOW               = 'onboarding';
    const NEEDS_CLARIFICATION           = 'needs clarification';
    const POST_ONBOARDING_EDIT          = 'post onboarding edit';

    // Razorx Experiment for BVS metadata
    const LIVE_MODE  = 'live';
    const ON         = 'on';

    // Config names
    const PERSONAL_PAN_OCR           = 'personal_pan_ocr';
    const BUSINESS_PAN_OCR           = 'business_pan_ocr';
    const GST_CERTIFICATE_OCR_CONFIG = 'gst_in_ocr';
    const MSME_OCR                   = 'msme_ocr';
    const SHOP_ESTABLISHMENT_OCR     = 'shop_establishment_ocr';
    const PARTNERSHIP_DEED_OCR       = 'partnership_deed_ocr';

    const CANCELLED_CHEQUE_OCR_PERSONAL_PAN             = 'cancelled_cheque_ocr_personal_pan';
    const CANCELLED_CHEQUE_OCR_BUSINESS_PAN             = 'cancelled_cheque_ocr_business_pan';
    const CANCELLED_CHEQUE_OCR_BUSINESS_OR_PROMOTER_PAN = 'cancelled_cheque_ocr_business_or_promoter_pan';
    const SHOP_ESTABLISHMENT_AUTH                       = 'shop_establishment_auth';
    const BANK_ACCOUNT_WITH_PERSONAL_PAN                = "bank_account_with_personal_pan";
    const BANK_ACCOUNT_WITH_BUSINESS_PAN                = "bank_account_with_business_pan";
    const BANK_ACCOUNT_WITH_BUSINESS_OR_PROMOTER_PAN    = "bank_account_with_business_or_promoter_pan";
    const AADHAR_BACK                                   = "aadhar_back";
    const AADHAAR_WITH_PAN                              = 'aadhaar_with_pan';
    const ADMIN_ROLE                                    = "admin";
    const ADMIN_LOGGED_IN_AS_MERCHANT_MESSAGE           = "(Admin Logged in as Merchant)";
    const ADMIN_IS_LOGGED_IN_AS_MERCHANT_HEADER         = "1";

    //
    // Response fields
    //
    const VALIDATION_ID   = 'validation_id';
    const VALIDATION_UNIT = 'validation_unit';
    const STATUS          = 'status';

    //
    // Artefact types in BVS
    //
    const PERSONAL_PAN       = 'personal_pan';
    const AADHAAR            = 'aadhaar';
    const CIN                = 'cin';
    const GSTIN              = 'gstin';
    const VOTERS_ID          = 'voters_id';
    const PASSPORT           = 'passport';
    const LLP_DEED           = 'llp_deed';
    const BUSINESS_PAN       = 'business_pan';
    const SHOP_ESTABLISHMENT = 'shop_establishment';
    const GST_CERTIFICATE    = 'gst_certificate';
    const MSME               = 'msme';
    const PARTNERSHIP_DEED   = 'partnership_deed';

    //
    // Company Search in BVS
    //
    const COMPANY_SEARCH    = 'company_search';
    const GET_GST_DETAILS   = 'get_gst_details';
    const SEARCH_DATA       = 'search_data';
    const RESULTS           = 'results';
    const ERROR_CODE        = 'code';
    const ERROR_DESCRIPTION = 'description';
    const IDENTITY_NUMBER   = 'identity_number';
    const IDENTITY_TYPE     = 'identity_type';
    const CLIENT            = 'client';
    const MERCHANT_ID       = 'merchant_id';


    // Platform
    const PG = 'pg';
    const RX = 'rx';

    // Owner Types
    const BANKING_ACCOUNT = 'banking_account';

    const BAS_DOCUMENT = 'bas_document';
    const MERCHANT     = 'merchant';

    const ARTEFACT_TYPE                   = 'artefact_type';
    const SUCCESS                         = 'success';
    const FAILURE                         = 'failure';
    const RAZORX_EXPERIMENT               = 'razorx_experiment';
    const AADHAR_ESIGN_SESSION_KEY_PREFIX = "aadhar_esign_session";
    const CUSTOM_CALLBACK_HANDLER         = 'custom_callback_handler';

    const FIELD_ARTEFACT_DETAILS_MAP      = [

        self::PARTNERSHIP_DEED             => [
            self::ARTEFACT_TYPE   => self::PARTNERSHIP_DEED,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
            self::CONFIG_NAME     => self::PARTNERSHIP_DEED_OCR
        ],
        Type::GST_CERTIFICATE             => [
            self::ARTEFACT_TYPE   => self::GSTIN,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
            self::CONFIG_NAME     => self::GST_CERTIFICATE_OCR_CONFIG
        ],
        Type::AADHAR_FRONT                => [
            self::ARTEFACT_TYPE   => self::AADHAAR,
            self::PROOF_INDEX     => '3',
            self::VALIDATION_UNIT => Constants::PROOF,
        ],
        Type::AADHAR_BACK                 => [
            self::ARTEFACT_TYPE   => self::AADHAAR,
            self::PROOF_INDEX     => '2',
            self::VALIDATION_UNIT => Constants::PROOF,
        ],
        Type::VOTER_ID_FRONT              => [
            self::ARTEFACT_TYPE   => self::VOTERS_ID,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
        ],
        Type::PASSPORT_FRONT              => [
            self::ARTEFACT_TYPE   => self::PASSPORT,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
        ],
        Type::PERSONAL_PAN                => [
            self::ARTEFACT_TYPE   => self::PERSONAL_PAN,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
        ],
        Type::BUSINESS_PAN_URL            => [
            self::ARTEFACT_TYPE   => self::BUSINESS_PAN,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
        ],
        Type::CANCELLED_CHEQUE            => [
            self::ARTEFACT_TYPE   => self::BANK_ACCOUNT,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
        ],
        Entity::SHOP_ESTABLISHMENT_NUMBER => [
            self::ARTEFACT_TYPE   => self::SHOP_ESTABLISHMENT,
            self::VALIDATION_UNIT => Constants::IDENTIFIER,
        ],
        Constant::GSTIN                   => [
            self::ARTEFACT_TYPE   => self::GSTIN,
            self::VALIDATION_UNIT => Constants::IDENTIFIER,
        ],
        Constant::LLPIN                   => [
            self::ARTEFACT_TYPE   => self::LLP_DEED,
            self::VALIDATION_UNIT => Constants::IDENTIFIER,
        ],
        Constant::CIN                     => [
            self::ARTEFACT_TYPE   => self::CIN,
            self::VALIDATION_UNIT => Constants::IDENTIFIER,
        ],
        Entity::BANK_ACCOUNT_NUMBER       => [
            self::ARTEFACT_TYPE   => self::BANK_ACCOUNT,
            self::VALIDATION_UNIT => Constants::IDENTIFIER,
        ],
        Constant::BUSINESS_PAN            => [
            self::ARTEFACT_TYPE   => self::BUSINESS_PAN,
            self::VALIDATION_UNIT => Constants::IDENTIFIER,
        ],
    ];

    const ENABLE_VERIFICATION_AFTER_FORM_SUBMISSION = [
        Type::BUSINESS_PAN_URL            => [],
        Type::PERSONAL_PAN                => [],
        Type::CANCELLED_CHEQUE            => [],
        Entity::SHOP_ESTABLISHMENT_NUMBER => [],
        Constant::GSTIN                   => [],
        self::LLPIN                       => [],
        self::CIN                         => [],
        Entity::BANK_ACCOUNT_NUMBER       => [
            self::RAZORX_EXPERIMENT => RazorxTreatment::BVS_PENNY_TESTING,
        ],
        Constant::BUSINESS_PAN            => [],
    ];
}
