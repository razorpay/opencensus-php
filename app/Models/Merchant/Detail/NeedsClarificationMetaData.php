<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Document\Type as DocumentType;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList as ReasonList;

class NeedsClarificationMetaData
{
    const DESCRIPTION    = 'description';
    const REASONS        = 'reasons';
    const OTHERS         = 'others';

    const REASON_MAPPING = [
        Entity::CONTACT_NAME               => [ReasonList::PROVIDE_POC],
        Entity::CONTACT_MOBILE             => [ReasonList::INVALID_CONTACT_NUMBER],
        Entity::BUSINESS_TYPE              => [ReasonList::IS_COMPANY_REG],
        Entity::BUSINESS_CATEGORY          => [ReasonList::SERVICES_OFFERED],
        Entity::BUSINESS_SUBCATEGORY       => [ReasonList::SERVICES_OFFERED],
        Entity::BUSINESS_WEBSITE           => [ReasonList::WEBSITE_NOT_LIVE],
        Entity::PROMOTER_PAN               => [ReasonList::UPDATE_DIRECTOR_PAN],
        Entity::COMPANY_PAN_NAME           => [ReasonList::UPDATE_DIRECTOR_PAN],
        Entity::BANK_ACCOUNT_NUMBER        => [ReasonList::UNABLE_TO_VALIDATE_ACC_NUMBER],
        Entity::BANK_ACCOUNT_NAME          => [ReasonList::UNABLE_TO_VALIDATE_BENEFICIARY_NAME],
        Entity::BANK_BRANCH_IFSC           => [ReasonList::UNABLE_TO_VALIDATE_IFSC],
        Entity::BUSINESS_PROOF_URL         => [ReasonList::SUBMIT_INCORPORATION_CERTIFICATE,
                                               ReasonList::SUBMIT_COMPLETE_PARTNERSHIP_DEED,
                                               ReasonList::SUBMIT_GSTIN_MSME_SHOPS_ESTAB_CERTIFICATE,
                                               ReasonList::SUBMIT_COMPLETE_TRUST_DEED,
                                               ReasonList::SUBMIT_SOCIETY_REG_CERTIFICATE,
                                               ReasonList::BUSINESS_PROOF_OUTDATED,
                                               ReasonList::ILLEGIBLE_DOC,
                                               ReasonList::SUBMIT_REG_BUSINESS_PAN_CARD,
        ],
        Entity::ADDRESS_PROOF_URL          => [ReasonList::UNABLE_TO_VALIDATE_ACC_NUMBER,
                                               ReasonList::UNABLE_TO_VALIDATE_BENEFICIARY_NAME,
                                               ReasonList::UNABLE_TO_VALIDATE_IFSC
        ],
        Entity::PROMOTER_ADDRESS_URL       => [ReasonList::SUBMIT_COMPLETE_DIRECTOR_ADDRESS_PROOF,
                                               ReasonList::SUBMIT_COMPLETE_AADHAAR,
                                               ReasonList::SUBMIT_COMPLETE_PASSPORT,
                                               ReasonList::SUBMIT_COMPLETE_ELECTION_CARD,
                                               ReasonList::ADDRESS_PROOF_OUTDATED
        ],
        DocumentType::AADHAR_BACK          => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::AADHAR_FRONT         => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::VOTER_ID_FRONT       => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::VOTER_ID_BACK        => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::DRIVER_LICENSE_BACK  => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::DRIVER_LICENSE_FRONT => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::PASSPORT_FRONT       => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::PASSPORT_BACK        => [ReasonList::ILLEGIBLE_DOC],
        DocumentType::CANCELLED_CHECK      => [ReasonList::ILLEGIBLE_DOC],
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
}
