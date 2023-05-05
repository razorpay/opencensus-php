<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Constants\Table;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\VerificationDetail\Constants as VerificationConstant;
class Constant
{

    const SYNC = 'sync';
    const ASYNC = 'async';

    //
    // Request fields
    //
    const BUSINESS_NAME     = 'business_name';
    const NAME_OF_PARTNERS  = 'name_of_partners';
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
    const DOCUMENT_TYPE     = 'document_type';
    const SITE_CHECK        = 'site_check';
    const WEBSITE_URL       = 'website_url';


    const ENRICHMENT_DETAIL_FIELDS = "enrichment_details_fields";

    const RULE_EXECUTION_LIST      = 'rule_execution_list';
    const RULE_EXECUTION_RESULT    = 'rule_execution_result';
    const REMARKS                  = 'remarks';
    const MATCH_PERCENTAGE         = 'match_percentage';

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
    const ONBOARDING_FLOW      = 'onboarding';
    const NEEDS_CLARIFICATION  = 'needs clarification';
    const POST_ONBOARDING_EDIT = 'post onboarding edit';

    //Manual Verification BVS
    const DATA                       = "data";
    const ACTIVATED                  = 'activated';
    const REJECTED                   = 'rejected';
    const MANUAL_VERIFICATION_STATUS = "status";
    const MERCHANT_DATA              = "merchant_data";
    const CLARIFICATION_DATA         = "clarification_data";
    const DOCUMENTS                  = "documents";
    const KYC_DETAILS                = "Kyc_details";
    const PAN_NAME                   = 'pan_name';
    const PROMOTER_PAN               = 'promoter_pan';
    const PROMOTER_PAN_NAME          = 'promoter_pan_name';
    const BANK_ACCOUNT_NUMBER        = 'bank_account_number';
    const ACTIVATED_MCC_PENDING      = 'activated_mcc_pending';
    const WORKFLOW_ACTION_ID         = 'workflow_action_id';
    const REJECTION_REASONS          = 'rejection_reasons';
    const MANUAL_VERIFICATION_FLOW   = 'manual_verification';

    // Razorx Experiment for BVS metadata
    const LIVE_MODE = 'live';
    const ON        = 'on';

    // Config names
    const PERSONAL_PAN_OCR                              = 'personal_pan_ocr';
    const BUSINESS_PAN_OCR                              = 'business_pan_ocr';
    const COMMON_MANUAL_VERIFICATION                    = 'common_manual_verification';
    const GST_CERTIFICATE_OCR_CONFIG                    = 'gst_in_ocr';
    const MSME_OCR                                      = 'msme_ocr';
    const SHOP_ESTABLISHMENT_OCR                        = 'shop_establishment_ocr';
    const PARTNERSHIP_DEED_OCR                          = 'partnership_deed_ocr';
    const CERTIFICATE_OF_INCORPORATION_OCR              = 'certificate_of_incorporation_ocr';
    const TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE_OCR    = 'trust_society_ngo_business_certificate_ocr';

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
    const GSTIN_WITH_BUSINESS_PAN_FOR_NO_DOC            = "gstin_with_business_pan_for_no_doc";
    const AADHAAR_FRONT_AND_BACK_OCR                    = "aadhaar_front_and_back";

    // Response fields
    const VALIDATION_ID     = 'validation_id';
    const VALIDATION_UNIT   = 'validation_unit';
    const STATUS            = 'status';
    const ID                = 'id';
    const COUNT             = 'count';
    const DOCUMENTS_DETAIL  = 'documents_detail';

    // Artefact type for signatory validation
    const SIGNATORY_VALIDATION                   = 'signatory_validation';

    // Artefact types in BVS
    const PERSONAL_PAN                           = 'personal_pan';
    const PERSONAL_PAN_FETCH                     = 'personal_pan_fetch';
    const AADHAAR                                = 'aadhaar';
    const CIN                                    = 'cin';
    const GSTIN                                  = 'gstin';
    const VOTERS_ID                              = 'voters_id';
    const PASSPORT                               = 'passport';
    const LLP_DEED                               = 'llp_deed';
    const BUSINESS_PAN                           = 'business_pan';
    const BUSINESS_PAN_FETCH                     = 'business_pan_fetch';
    const BUSINESS_WEBSITE                       = 'business_website';
    const SHOP_ESTABLISHMENT                     = 'shop_establishment';
    const GST_CERTIFICATE                        = 'gst_certificate';
    const MSME                                   = 'msme';
    const COMMON                                 = 'common';
    const PARTNERSHIP_DEED                       = 'partnership_deed';
    const CERTIFICATE_OF_INCORPORATION           = 'certificate_of_incorporation';
    const TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE = 'trust_society_ngo_business_certificate';

    // Company Search in BVS
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

    const ONLINE_PROVIDER                 = 'online_provider';
    const VALUE                           = 'value';

    const AADHAAR_EKYC                    = 'AADHAAR_EKYC';

    const MCC_CATEGORISATION              = 'mcc_categorisation';
    const WEBSITE_POLICY                  = 'website_policy';
    const NEGATIVE_KEYWORDS               = 'negative_keywords';

    const MCC_CATEGORISATION_WEBSITE      = 'mcc_categorisation_website';
    const MCC_CATEGORISATION_GSTIN        = 'mcc_categorisation_gstin';

    const FIELD_ARTEFACT_DETAILS_MAP = [
        self::PARTNERSHIP_DEED            => [
            self::ARTEFACT_TYPE   => self::PARTNERSHIP_DEED,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
            self::CONFIG_NAME     => self::PARTNERSHIP_DEED_OCR
        ],
        self::CERTIFICATE_OF_INCORPORATION => [
            self::ARTEFACT_TYPE   => self::CERTIFICATE_OF_INCORPORATION,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
            self::CONFIG_NAME     => self::CERTIFICATE_OF_INCORPORATION_OCR
        ],
        self::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE => [
            self::ARTEFACT_TYPE   => self::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE,
            self::PROOF_INDEX     => '1',
            self::VALIDATION_UNIT => Constants::PROOF,
            self::CONFIG_NAME     => self::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE_OCR
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
        Entity::BANK_ACCOUNT_NUMBER       => [],
        Constant::BUSINESS_PAN            => [],
    ];

    const MASKED_KEYS_FOR_LOGGING = [
        self::DETAILS
    ];

    public const ARTEFACT_STATUS_ATTRIBUTE_MAPPING = [
        Constant::PERSONAL_PAN . '-' . BvsValidationConstants::IDENTIFIER                             => [Table::MERCHANT_DETAIL, Entity::POI_VERIFICATION_STATUS],
        Constant::BUSINESS_PAN . '-' . BvsValidationConstants::IDENTIFIER                             => [Table::MERCHANT_DETAIL, Entity::COMPANY_PAN_VERIFICATION_STATUS],
        Constant::BANK_ACCOUNT . '-' . BvsValidationConstants::IDENTIFIER                             => [Table::MERCHANT_DETAIL, Entity::BANK_DETAILS_VERIFICATION_STATUS],
        Constant::CIN . '-' . BvsValidationConstants::IDENTIFIER                                      => [Table::MERCHANT_DETAIL, Entity::CIN_VERIFICATION_STATUS],
        Constant::LLP_DEED . '-' . BvsValidationConstants::IDENTIFIER                                 => [Table::MERCHANT_DETAIL, Entity::CIN_VERIFICATION_STATUS],
        Constant::CIN . '-' . BvsValidationConstants::PROOF                                           => [Table::MERCHANT_DETAIL, Entity::CIN_VERIFICATION_STATUS],
        Constant::LLP_DEED . '-' . BvsValidationConstants::PROOF                                      => [Table::MERCHANT_DETAIL, Entity::CIN_VERIFICATION_STATUS],
        Constant::GSTIN . '-' . BvsValidationConstants::IDENTIFIER                                    => [Table::MERCHANT_DETAIL, Entity::GSTIN_VERIFICATION_STATUS],
        Constant::SHOP_ESTABLISHMENT . '-' . BvsValidationConstants::IDENTIFIER                       => [Table::MERCHANT_DETAIL, Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS],
        Constant::BANK_ACCOUNT . '-' . BvsValidationConstants::PROOF                                  => [Table::MERCHANT_DETAIL, Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS],
        Constant::PERSONAL_PAN . '-' . BvsValidationConstants::PROOF                                  => [Table::MERCHANT_DETAIL, Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS],
        Constant::BUSINESS_PAN . '-' . BvsValidationConstants::PROOF                                  => [Table::MERCHANT_DETAIL, Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS],
        Constant::MSME . '-' . BvsValidationConstants::PROOF                                          => [Table::MERCHANT_DETAIL, Entity::MSME_DOC_VERIFICATION_STATUS],
        Constant::MSME . '-' . BvsValidationConstants::IDENTIFIER                                     => [Table::MERCHANT_DETAIL, Entity::MSME_DOC_VERIFICATION_STATUS],
        Constant::SHOP_ESTABLISHMENT . '-' . BvsValidationConstants::PROOF                            => [Table::MERCHANT_VERIFICATION_DETAIL, VerificationConstant::SHOP_ESTABLISHMENT, "doc"],
        Constant::GSTIN . '-' . BvsValidationConstants::PROOF                                         => [Table::MERCHANT_VERIFICATION_DETAIL, VerificationConstant::GSTIN, "doc"],
        Constant::PARTNERSHIP_DEED . '-' . BvsValidationConstants::PROOF                              => [Table::MERCHANT_VERIFICATION_DETAIL, Constant::PARTNERSHIP_DEED, "doc"],
        Constant::PARTNERSHIP_DEED . '-' . BvsValidationConstants::IDENTIFIER                         => [Table::MERCHANT_VERIFICATION_DETAIL, Constant::PARTNERSHIP_DEED, "number"],
        Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE . '-' . BvsValidationConstants::PROOF        => [Table::MERCHANT_VERIFICATION_DETAIL, Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE, "doc"],
        Constant::AADHAAR . '-' . BvsValidationConstants::IDENTIFIER                                  => [Table::STAKEHOLDER, StakeholderEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS],
        Constant::VOTERS_ID . '-' . BvsValidationConstants::IDENTIFIER                                => [Table::MERCHANT_DETAIL, Entity::POA_VERIFICATION_STATUS],
        Constant::PASSPORT . '-' . BvsValidationConstants::IDENTIFIER                                 => [Table::MERCHANT_DETAIL, Entity::POA_VERIFICATION_STATUS],
        Constant::VOTERS_ID . '-' . BvsValidationConstants::PROOF                                     => [Table::MERCHANT_DETAIL, Entity::POA_VERIFICATION_STATUS],
        Constant::PASSPORT . '-' . BvsValidationConstants::PROOF                                      => [Table::MERCHANT_DETAIL, Entity::POA_VERIFICATION_STATUS],
        Constant::AADHAAR . '-' . BvsValidationConstants::PROOF                                       => [Table::MERCHANT_DETAIL, Entity::POA_VERIFICATION_STATUS]
    ];

    public const SIGNATORY_ARTEFACTS_BUSINESS_TYPE_MAPPING = [

        BusinessType::PUBLIC_LIMITED                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::CIN . '-' . VerificationConstant::NUMBER,
        ],
        BusinessType::PRIVATE_LIMITED                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::CIN . '-' . VerificationConstant::NUMBER,
        ],
        BusinessType::LLP                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::LLP_DEED . '-' . VerificationConstant::NUMBER,
        ],
        BusinessType::PARTNERSHIP                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::PARTNERSHIP_DEED . '-' . VerificationConstant::DOC,
        ],
        BusinessType::TRUST                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE . '-' . VerificationConstant::DOC,
        ],
        BusinessType::SOCIETY                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE . '-' . VerificationConstant::DOC,
        ],
        BusinessType::NGO                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE . '-' . VerificationConstant::DOC,
        ],
        BusinessType::PROPRIETORSHIP                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
            Constant::SHOP_ESTABLISHMENT . '-' . VerificationConstant::DOC,
            Constant::SHOP_ESTABLISHMENT . '-' . VerificationConstant::NUMBER,
            Constant::MSME . '-' . VerificationConstant::DOC,
        ],
        BusinessType::HUF                 => [
            Constant::GSTIN . '-' . VerificationConstant::NUMBER,
        ],
    ];

    const EXCLUDED_CONFIGS = [
        self::GSTIN,
        self::BANK_ACCOUNT_WITH_PERSONAL_PAN,
        self::BANK_ACCOUNT_WITH_BUSINESS_PAN,
        self::BANK_ACCOUNT_WITH_BUSINESS_OR_PROMOTER_PAN
    ];

    const COMPLIANCE_STATUS               = 'compliance_status';
    const IS_ANY_DELAY                    = 'is_any_delay';
    const IS_DEFAULTER                    = 'is_defaulter';

    const ACCEPTED_VERIFICATION_TYPE = [
        self::AADHAAR_EKYC
    ];
}
