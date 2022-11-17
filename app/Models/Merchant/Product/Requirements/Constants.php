<?php

namespace RZP\Models\Merchant\Product\Requirements;

use PhpParser\Comment\Doc;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Stakeholder\Entity;

class Constants
{
    const ACCOUNT_DOCUMENTS_RESOLUTION_URL      = '/accounts/{accountId}/documents';
    const ACCOUNT_TNC_ACCEPTANCE_RESOLUTION_URL = '/accounts/{accountId}/tnc';
    const STAKEHOLDER_DOCUMENTS_RESOLUTION_URL  = '/accounts/{accountId}/stakeholders/{stakeholderId}/documents';
    const ACCOUNTS_RESOLUTION_URL               = '/accounts/{accountId}';
    const STAKEHOLDER_UPDATE_RESOLUTION_URL     = '/accounts/{accountId}/stakeholders/{stakeholderId}';
    const STAKEHOLDER_CREATE_RESOLUTION_URL     = '/accounts/{accountId}/stakeholders';
    const PAYMENT_CONFIG_RESOLUTION_URL         = '/accounts/{accountId}/products/{merchantProductConfigId}';
    const STAKEHOLDER_ID_PLACEHOLDER            = '{stakeholderId}';
    const ACCOUNT_ID_PLACEHOLDER                = '{accountId}';
    const MERCHANT_PRODUCT_ID_PLACEHOLDER       = '{merchantProductConfigId}';


    const ENTITY_RESOLUTION_URL_MAPPING = [
        E::MERCHANT => [
            'document' => self::ACCOUNT_DOCUMENTS_RESOLUTION_URL,
            'field'    => self::ACCOUNTS_RESOLUTION_URL
        ],

        E::STAKEHOLDER . self::CREATE => [
            'field' => self::STAKEHOLDER_CREATE_RESOLUTION_URL
        ],

        E::STAKEHOLDER . self::UPDATE => [
            'field' => self::STAKEHOLDER_UPDATE_RESOLUTION_URL
        ],

        E::STAKEHOLDER => [
            'document' => self::STAKEHOLDER_DOCUMENTS_RESOLUTION_URL
        ],

        Util\Constants::CHECKOUT => [
            'field' => self::PAYMENT_CONFIG_RESOLUTION_URL
        ]
    ];

    const SETTLEMENT_FIELDS = [
        Detail\Entity::BANK_BRANCH_IFSC,
        Detail\Entity::BANK_ACCOUNT_NAME,
        Detail\Entity::BANK_ACCOUNT_NUMBER
    ];

    const BUSINESS_REQUIREMENT_FIELDS = [
        Detail\Entity::BUSINESS_CATEGORY,
        Detail\Entity::BUSINESS_SUBCATEGORY,
        Detail\Entity::BUSINESS_TYPE
    ];

    const LINKED_ACCOUNT_BUSINESS_REQUIREMENT_FIELDS = [
        Detail\Entity::BUSINESS_TYPE
    ];

    const REQUIRED_OTP_FIELDS = [
        Util\Constants::CONTACT_MOBILE
    ];

    //Requirements array constants
    const INTERNAL_STATUS = [BvsValidation\Constants::INITIATED, BvsValidation\Constants::PENDING, BvsValidation\Constants::FAILED];
    const NOT_APPLICABLE  = 'not_applicable';
    const FIELD_REFERENCE = 'field_reference';
    const REASON_CODE     = 'reason_code';
    const RESOLUTION_URL  = 'resolution_url';
    const STATUS          = 'status';
    const ENTITY          = 'entity';
    const FIELD           = 'field';
    const DOCUMENT        = 'document';
    const DESCRIPTION     = 'description';
    const ACCEPTED        = 'accepted';
    const TNC_ACCEPTED    = 'tnc_accepted';
    const OTP             = 'otp';
    const IP              = 'ip';

    //Reason codes
    const FIELD_MISSING             = 'field_missing';
    const DOCUMENT_MISSING          = 'document_missing';
    const FIELD_INVALID             = 'field_invalid';
    const DOCUMENT_INVALID          = 'document_invalid';
    const DOCUMENT_DETAILS_MISMATCH = 'document_details_mismatch';
    const FIELD_MISMATCH            = 'field_mismatch';

    //status
    const REQUIRED            = 'required';
    const OPTIONAL            = 'optional';
    const NEEDS_CLARIFICATION = 'needs_clarification';

    //Helper variables
    const FIELDS          = 'fields';
    const DOCUMENTS       = 'documents';
    const DOCUMENT_FIELDS = 'document_fields';
    const UPDATE          = '_update';
    const CREATE          = '_create';

    const INSTANT_ACTIVATION_LIMIT_BREACH_DESCRIPTION = 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.';

    const ARTEFACT_STATUS_MAPPING = [
        Detail\Entity::COMPANY_CIN               => Detail\Entity::CIN_VERIFICATION_STATUS,
        Detail\Entity::COMPANY_PAN               => Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS,
        Detail\Entity::GSTIN                     => Detail\Entity::GSTIN_VERIFICATION_STATUS,
        Detail\Entity::BANK_ACCOUNT_NUMBER       => Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS,
        Detail\Entity::SHOP_ESTABLISHMENT_NUMBER => Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS,
        Detail\Entity::PROMOTER_PAN              => Detail\Entity::POI_VERIFICATION_STATUS,
        Document\Type::AADHAR_FRONT              => Detail\Entity::POA_VERIFICATION_STATUS,
        Document\Type::VOTER_ID_FRONT            => Detail\Entity::POA_VERIFICATION_STATUS,
        Document\Type::PASSPORT_FRONT            => Detail\Entity::POA_VERIFICATION_STATUS,
        Document\Type::BUSINESS_PAN_URL          => Detail\Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS,
        Document\Type::PERSONAL_PAN              => Detail\Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS,
        Document\Type::CANCELLED_CHEQUE          => Detail\Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS,

    ];

    const NO_DOC_OPTIONAL_DOC_FIELDS = [
        Document\Type::CANCELLED_CHEQUE
    ];
}
