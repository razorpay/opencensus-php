<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Document\Type as DocumentType;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstant;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList as ReasonList;
use RZP\Models\Merchant\Detail\DeDupe\Constants as DedupeConstant;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessConstants;


class NeedsClarificationMetaData
{
    const DESCRIPTION                              = 'description';
    const REASONS                                  = 'reasons';
    const OTHERS                                   = 'others';
    const FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY = 'FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY';
    const NEEDS_CLARIFICATION_VERSION              = 'needs_clarification_version';
    const VERSION_V1                               = 'V1';
    const VERSION_V2                               = 'V2';

    const REASON_MAPPING = [
        Entity::CONTACT_NAME                    => [ReasonList::PROVIDE_POC],
        Entity::CONTACT_MOBILE                  => [ReasonList::INVALID_CONTACT_NUMBER],
        Entity::BUSINESS_TYPE                   => [ReasonList::IS_COMPANY_REG],
        Entity::BUSINESS_WEBSITE                => [ReasonList::WEBSITE_NOT_LIVE],
        BusinessConstants::PLAYSTORE_URL        => [ReasonList::WEBSITE_NOT_LIVE],
        BusinessConstants::APPSTORE_URL         => [ReasonList::WEBSITE_NOT_LIVE],
        Entity::BUSINESS_NAME                   => [ReasonList::COMPANY_NAME_NOT_MATCHED],
        Entity::PROMOTER_PAN_NAME               => [ReasonList::SIGNATORY_NAME_NOT_MATCHED],
        Entity::GSTIN                           => [ReasonList::INVALID_GSTIN_NUMBER,
                                                    ReasonList::GSTIN_DATA_UNAVAILABLE],
        Entity::SHOP_ESTABLISHMENT_NUMBER       => [ReasonList::INVALID_SHOP_ESTABLISHMENT_NUMBER,
                                                    ReasonList::SHOP_ESTABLISHMENT_DATA_UNAVAILABLE],
        Entity::COMPANY_CIN                     => [ReasonList::INVALID_CIN_NUMBER,
                                                    ReasonList::CIN_DATA_UNAVAILABLE,
                                                    ReasonList::INVALID_LLPIN_NUMBER,
                                                    ReasonList::LLPIN_DATA_UNAVAILABLE],
        Entity::PROMOTER_PAN                    => [ReasonList::INVALID_PERSONAL_PAN_NUMBER,
                                                    ReasonList::UPDATE_DIRECTOR_PAN,
                                                    ReasonList::UPDATE_PROPRIETOR_PAN,],
        Entity::COMPANY_PAN                     => [ReasonList::INVALID_COMPANY_PAN_NUMBER],
        Entity::COMPANY_PAN_NAME                => [ReasonList::UPDATE_DIRECTOR_PAN],
        Entity::BANK_ACCOUNT_NUMBER             => [ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST,
                                                    ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_UNREGISTERED,
                                                    ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP],
        Entity::BUSINESS_PROOF_URL              => [ReasonList::SUBMIT_INCORPORATION_CERTIFICATE,
                                                    ReasonList::SUBMIT_COMPLETE_PARTNERSHIP_DEED,
                                                    ReasonList::SUBMIT_GSTIN_MSME_SHOPS_ESTAB_CERTIFICATE,
                                                    ReasonList::SUBMIT_COMPLETE_TRUST_DEED,
                                                    ReasonList::SUBMIT_SOCIETY_REG_CERTIFICATE,
                                                    ReasonList::BUSINESS_PROOF_OUTDATED,
                                                    ReasonList::ILLEGIBLE_DOC,
                                                    ReasonList::SUBMIT_REG_BUSINESS_PAN_CARD,
        ],
        Entity::BUSINESS_PAN_URL                => [ReasonList::SUBMIT_COMPANY_PAN,
                                                    ReasonList::SUBMIT_PROPRIETOR_PAN,
        ],
        Entity::ADDRESS_PROOF_URL               => [ReasonList::UNABLE_TO_VALIDATE_ACC_NUMBER,
                                                    ReasonList::UNABLE_TO_VALIDATE_BENEFICIARY_NAME,
                                                    ReasonList::UNABLE_TO_VALIDATE_IFSC,
                                                    ReasonList::RESUBMIT_CANCELLED_CHEQUE,
        ],
        Entity::PROMOTER_ADDRESS_URL            => [ReasonList::SUBMIT_COMPLETE_DIRECTOR_ADDRESS_PROOF,
                                                    ReasonList::SUBMIT_COMPLETE_AADHAAR,
                                                    ReasonList::SUBMIT_COMPLETE_PASSPORT,
                                                    ReasonList::SUBMIT_COMPLETE_ELECTION_CARD,
                                                    ReasonList::ADDRESS_PROOF_OUTDATED,
                                                    ReasonList::SUBMIT_DRIVING_LICENSE,
        ],
        DocumentType::AADHAR_BACK               => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::AADHAR_FRONT              => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::VOTER_ID_FRONT            => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::VOTER_ID_BACK             => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::DRIVER_LICENSE_BACK       => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::DRIVER_LICENSE_FRONT      => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::PASSPORT_FRONT            => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::PASSPORT_BACK             => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::CANCELLED_CHEQUE          => [ReasonList::ILLEGIBLE_DOC,
                                                    ReasonList::UNABLE_TO_VALIDATE_ACC_NUMBER,
                                                    ReasonList::UNABLE_TO_VALIDATE_BENEFICIARY_NAME,
                                                    ReasonList::UNABLE_TO_VALIDATE_IFSC,
                                                    ReasonList::RESUBMIT_CANCELLED_CHEQUE,
        ],
        DocumentType::ARTICLE_OF_ASSOCIATION    => [ReasonList::AUTHORIZED_SIGNATORY_MISMATCH,
                                                    ReasonList::PROVIDE_AUTHORIZED_SIGNATORY_SIGNED_AND_SEALED_DOCUMENT],
        DocumentType::MEMORANDUM_OF_ASSOCIATION => [ReasonList::AUTHORIZED_SIGNATORY_MISMATCH,
                                                    ReasonList::PROVIDE_AUTHORIZED_SIGNATORY_SIGNED_AND_SEALED_DOCUMENT]
    ];
    const MERCHANT_REASON_MAPPING = [
        Entity::BANK_ACCOUNT_NUMBER             => [ReasonList::UNABLE_TO_VALIDATE_ACC_NUMBER,],

        Entity::BANK_ACCOUNT_NAME               => [ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST,
                                                    ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_UNREGISTERED,
                                                    ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP,
                                                    ReasonList::UNABLE_TO_VALIDATE_BENEFICIARY_NAME],

        Entity::BANK_BRANCH_IFSC                => [ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST,
                                                    ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_UNREGISTERED,
                                                    ReasonList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP,
                                                    ReasonList::UNABLE_TO_VALIDATE_IFSC]
        ];

    const BUSINESS_TYPE_REASON_CODE_MAPPING=[
        BusinessType::PROPRIETORSHIP            => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST,
        BusinessType::PARTNERSHIP               => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP,
        BusinessType::HUF                       => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP,
        BusinessType::PRIVATE_LIMITED           => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP,
        BusinessType::PUBLIC_LIMITED            => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP,
        BusinessType::LLP                       => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP,
        BusinessType::TRUST                     => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST,
        BusinessType::SOCIETY                   => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST,
        BusinessType::NGO                       => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST,
        BusinessType::INDIVIDUAL                => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_UNREGISTERED,
        BusinessType::NOT_YET_REGISTERED        => NeedsClarificationReasonsList::BANK_ACCOUNT_CHANGE_REQUEST_FOR_UNREGISTERED,
        BusinessType::EDUCATIONAL_INSTITUTES    => NeedsClarificationReasonsList::UNABLE_TO_VALIDATE_ACC_NUMBER,
        BusinessType::OTHER                     => NeedsClarificationReasonsList::UNABLE_TO_VALIDATE_ACC_NUMBER,
    ];
    /**
     * Version :
     *  V1 : If Verification is done by any system other than bvs
     *  V2 : If verification is done by bvs
     */
    const RELATED_FIELDS_METADATA = [
        Constants::PROMOTER_PAN => [
            Constants::RELATED_FIELDS => [
                [
                    Constants::FIELD_NAME                              => Entity::PROMOTER_PAN_NAME,
                    Constants::CAN_RF_EXIST_INDEPENDENTLY              => true,
                ],
            ],
        ],
        Constants::BANK_ACCOUNT_NUMBER => [
            Constants::RELATED_FIELDS => [
                    [
                        Constants::FIELD_NAME                          => DocumentType::CANCELLED_CHEQUE,
                        Constants::CAN_RF_EXIST_INDEPENDENTLY          => false,
                    ],
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_ACCOUNT_NAME,
                        Constants::CAN_RF_EXIST_INDEPENDENTLY          => false,
                    ],
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_BRANCH_IFSC,
                        Constants::CAN_RF_EXIST_INDEPENDENTLY          => false,
                    ],
            ],
        ],
        Entity::COMPANY_PAN => [
            Constants::RELATED_FIELDS => [
                [
                    Constants::FIELD_NAME                          => Entity::COMPANY_PAN_NAME,
                    Constants::CAN_RF_EXIST_INDEPENDENTLY          => true,
                ],
            ],
        ]
    ];

    const LINKED_ACCOUNT_RELATED_FIELDS_METADATA = [
        Constants::BANK_ACCOUNT_NUMBER => [
            Constants::RELATED_FIELDS => [
                [
                    Constants::FIELD_NAME                          => Entity::BANK_ACCOUNT_NAME,
                    Constants::CAN_RF_EXIST_INDEPENDENTLY          => false,
                ],
                [
                    Constants::FIELD_NAME                          => Entity::BANK_BRANCH_IFSC,
                    Constants::CAN_RF_EXIST_INDEPENDENTLY          => false,
                ],
            ],
        ],
    ];

    const SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA = [
        Constants::PERSONAL_PAN_IDENTIFIER  => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::PERSONAL_PAN,
            Constants::FIELD_NAME                          => Entity::PROMOTER_PAN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::DEDUPE_CHECK_KEY                    => Entity::PROMOTER_PAN,
            Constants::REASON_MAPPING                      => [
                DedupeConstant::FIELD_ALREADY_EXIST        => ReasonList::FIELD_ALREADY_EXIST,
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_PERSONAL_PAN_NUMBER,
            ],
        ],
        Constants::COMPANY_PAN_IDENTIFIER   => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::BUSINESS_PAN,
            Constants::FIELD_NAME                          => Entity::COMPANY_PAN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::DEDUPE_CHECK_KEY                    => Entity::COMPANY_PAN,
            Constants::REASON_MAPPING                      => [
                DedupeConstant::FIELD_ALREADY_EXIST        => ReasonList::FIELD_ALREADY_EXIST,
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_COMPANY_PAN_NUMBER,
            ],
        ],
        Constants::GSTIN_IDENTIFER     => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::GSTIN,
            Constants::FIELD_NAME                          => Entity::GSTIN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::REASON_MAPPING                      => [
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_GSTIN_NUMBER,
                BvsValidationConstant::DATA_UNAVAILABLE      => ReasonList::GSTIN_DATA_UNAVAILABLE,
                BvsValidationConstant::RULE_EXECUTION_FAILED => ReasonList::GSTIN_DATA_NOT_MATCHED,
            ],
        ],
        Constants::CIN_IDENTIFER       => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::CIN,
            Constants::FIELD_NAME                          => Entity::COMPANY_CIN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::REASON_MAPPING                      => [
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_CIN_NUMBER,
                BvsValidationConstant::DATA_UNAVAILABLE      => ReasonList::CIN_DATA_UNAVAILABLE,
                BvsValidationConstant::RULE_EXECUTION_FAILED => ReasonList::CIN_DATA_NOT_MATCHED,
            ],
        ],
        Constants::LLPIN_IDENTIFIER    => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::LLPIN,
            Constants::FIELD_NAME                          => Entity::COMPANY_CIN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::REASON_MAPPING                      => [
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_LLPIN_NUMBER,
                BvsValidationConstant::DATA_UNAVAILABLE      => ReasonList::LLPIN_DATA_UNAVAILABLE,
                BvsValidationConstant::RULE_EXECUTION_FAILED => ReasonList::LLPIN_DATA_NOT_MATCHED,

            ],],
        Constants::BANK_ACCOUNT_NUMBER => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V1,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::BANK_ACCOUNT,
            Constants::FIELD_NAME                          => DocumentType::CANCELLED_CHEQUE,
            Constants::FIELD_TYPE                          => Constants::DOCUMENT,
            Constants::ADDITIONAL_DETAILS => [
                Constants::FIELDS => [
                    [
                        Constants::FIELD_NAME                          => DocumentType::CANCELLED_CHEQUE,
                        Constants::FIELD_TYPE                          => Constants::DOCUMENT,
                    ],
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_ACCOUNT_NAME,
                        Constants::FIELD_TYPE                          => Constants::TEXT,
                    ],
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_ACCOUNT_NUMBER,
                        Constants::FIELD_TYPE                          => Constants::TEXT,
                    ],
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_BRANCH_IFSC,
                        Constants::FIELD_TYPE                          => Constants::TEXT,
                    ],
                ],
            ],
        ],
        Constants::SHOP_ESTABLISHMENT_IDENTIFIER => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Entity::SHOP_ESTABLISHMENT_NUMBER,
            Constants::FIELD_NAME                          => Entity::SHOP_ESTABLISHMENT_NUMBER,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::REASON_MAPPING                      => [
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_SHOP_ESTABLISHMENT_NUMBER,
                BvsValidationConstant::DATA_UNAVAILABLE      => ReasonList::SHOP_ESTABLISHMENT_DATA_UNAVAILABLE,
                BvsValidationConstant::RULE_EXECUTION_FAILED => ReasonList::SHOP_ESTABLISHMENT_DATA_NOT_MATCHED,
            ],
        ],
        Constants::CONTACT_MOBILE => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Entity::CONTACT_MOBILE,
            Constants::FIELD_NAME                          => Entity::CONTACT_MOBILE,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::DEDUPE_CHECK_KEY                    => Entity::CONTACT_MOBILE,
            Constants::REASON_MAPPING                      => [
                DedupeConstant::FIELD_ALREADY_EXIST          => ReasonList::FIELD_ALREADY_EXIST
            ],
        ]
    ];

    const LINKED_ACCOUNT_SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA = [
        Constants::PERSONAL_PAN_IDENTIFIER  => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::PERSONAL_PAN,
            Constants::FIELD_NAME                          => Entity::PROMOTER_PAN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::DEDUPE_CHECK_KEY                    => Entity::PROMOTER_PAN,
            Constants::REASON_MAPPING                      => [
                DedupeConstant::FIELD_ALREADY_EXIST        => ReasonList::FIELD_ALREADY_EXIST,
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_PERSONAL_PAN_NUMBER,
                BvsValidationConstant::RULE_EXECUTION_FAILED => ReasonList::PERSONAL_PAN_DATA_NOT_MATCHED,
                BvsValidationConstant::SPAM_DETECTED_ERROR   => ReasonList::PERSONAL_PAN_SPAM_DETECTED
            ],
        ],
        Constants::COMPANY_PAN_IDENTIFIER   => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::BUSINESS_PAN,
            Constants::FIELD_NAME                          => Entity::COMPANY_PAN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::DEDUPE_CHECK_KEY                    => Entity::COMPANY_PAN,
            Constants::REASON_MAPPING                      => [
                DedupeConstant::FIELD_ALREADY_EXIST        => ReasonList::FIELD_ALREADY_EXIST,
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_COMPANY_PAN_NUMBER,
                BvsValidationConstant::RULE_EXECUTION_FAILED => ReasonList::COMPANY_PAN_DATA_NOT_MATCHED,
                BvsValidationConstant::SPAM_DETECTED_ERROR   => ReasonList::COMPANY_PAN_SPAM_DETECTED
            ],
        ],
        Constants::GSTIN_IDENTIFER     => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V2,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::GSTIN,
            Constants::FIELD_NAME                          => Entity::GSTIN,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::REASON_MAPPING                      => [
                BvsValidationConstant::INPUT_DATA_ISSUE      => ReasonList::INVALID_GSTIN_NUMBER,
                BvsValidationConstant::DATA_UNAVAILABLE      => ReasonList::GSTIN_DATA_UNAVAILABLE,
                BvsValidationConstant::RULE_EXECUTION_FAILED => ReasonList::GSTIN_NUMBER_NOT_MATCHED,
                BvsValidationConstant::SPAM_DETECTED_ERROR   => ReasonList::GSTIN_SPAM_DETECTED
            ],
        ],
        Constants::BANK_ACCOUNT_NUMBER => [
            self::NEEDS_CLARIFICATION_VERSION              => self::VERSION_V1,
            self::FIELD_ARTEFACT_DETAILS_MAP_REFERENCE_KEY => Constant::BANK_ACCOUNT,
            Constants::FIELD_NAME                          => Entity::BANK_ACCOUNT_NUMBER,
            Constants::FIELD_TYPE                          => Constants::TEXT,
            Constants::ADDITIONAL_DETAILS => [
                Constants::FIELDS => [
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_ACCOUNT_NAME,
                        Constants::FIELD_TYPE                          => Constants::TEXT,
                    ],
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_ACCOUNT_NUMBER,
                        Constants::FIELD_TYPE                          => Constants::TEXT,
                    ],
                    [
                        Constants::FIELD_NAME                          => Entity::BANK_BRANCH_IFSC,
                        Constants::FIELD_TYPE                          => Constants::TEXT,
                    ],
                ],
            ],
        ],
    ];

    // Supported additional text fields from merchants
    const TEXT_BUSINESS_DESCRIPTION = 'business_description';

    /**
     * @param string $textField
     *
     * @return bool
     */
    public static function isValidPredefinedAdditionalField(string $textField): bool
    {
        $key = __CLASS__ . '::' . 'TEXT_' . strtoupper($textField);

        return ((defined($key) === true) and (constant($key) === $textField));
    }

    public static function getLinkedAccountSystemBasedNeedsClarificationMetaData(): array
    {
        return array_merge(self::SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA, self::LINKED_ACCOUNT_SYSTEM_BASED_NEEDS_CLARIFICATION_METADATA);
    }

    public static function getLinkedAccountRelatedFieldsMetaData(): array
    {
        return array_merge(self::RELATED_FIELDS_METADATA, self::LINKED_ACCOUNT_RELATED_FIELDS_METADATA);
    }
}
