<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Document\TypeGroups;

class ValidationFields
{

    const POA_DOCUMENTS = 'poa_documents';

    const DASHBOARD_FIELDS = [
            Entity::ADDRESS_PROOF_URL,
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
            Entity::BUSINESS_DBA,
            Entity::BUSINESS_INTERNATIONAL,
            Entity::BUSINESS_NAME,
            Entity::BUSINESS_OPERATION_ADDRESS,
            Entity::BUSINESS_OPERATION_CITY,
            Entity::BUSINESS_OPERATION_PIN,
            Entity::BUSINESS_OPERATION_STATE,
            Entity::BUSINESS_PAN_URL,
            Entity::BUSINESS_PROOF_URL,
            Entity::BUSINESS_REGISTERED_ADDRESS,
            Entity::BUSINESS_REGISTERED_CITY,
            Entity::BUSINESS_REGISTERED_PIN,
            Entity::BUSINESS_REGISTERED_STATE,
            Entity::BUSINESS_TYPE,
            Entity::CONTACT_EMAIL,
            Entity::CONTACT_MOBILE,
            Entity::CONTACT_NAME,
            Entity::PROMOTER_ADDRESS_URL,
            Entity::PROMOTER_PAN_NAME,
    ];

    // There are limited total fields for Individual and Not-yet-registered business types
    const DASHBOARD_UNREGISTERED_LIMITED = [
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
            Entity::BUSINESS_REGISTERED_ADDRESS,
            Entity::BUSINESS_REGISTERED_CITY,
            Entity::BUSINESS_REGISTERED_PIN,
            Entity::BUSINESS_REGISTERED_STATE,
            Entity::BUSINESS_TYPE,
            Entity::CONTACT_EMAIL,
            Entity::CONTACT_MOBILE,
            Entity::CONTACT_NAME,
            Entity::PROMOTER_PAN_NAME,
    ];

    /**
     * This contains documents required for unregistered business
     */
    const UNREGISTERED_DOCUMENT_FIELDS = [
        self::POA_DOCUMENTS => [
            [Type::AADHAR_FRONT, Type::AADHAR_BACK],
            [Type::PASSPORT_FRONT, Type::PASSPORT_BACK],
            [Type::VOTER_ID_FRONT, Type::VOTER_ID_BACK],
            [Type::DRIVER_LICENSE_FRONT],
        ]
    ];


    const BANK_ACCOUNT_FIELDS = [
        Entity::BANK_ACCOUNT_NAME,
        Entity::BANK_ACCOUNT_NUMBER,
        Entity::BANK_BRANCH_IFSC,
    ];

    /**
     * Fields required when merchant is an NGO
     * for submitting the activation form
     *
     * @var array
     */
    const NGO_MERCHANT_FIELDS = [
        Entity::FORM_12A_URL,
        Entity::FORM_80G_URL,
    ];

    /**
     * Fields required for all types of marketplace linked accounts
     * for submitting the activation form
     *
     * @var array
     */
    const MARKETPLACE_ACCOUNT_FIELDS = [
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
            Entity::BUSINESS_NAME,
            Entity::BUSINESS_TYPE,
    ];

    /**
     * Additional fields required when the Marketplace merchant is restricted
     *
     * Restricted merchants have flag `linked_account_kyc = 1`, and we require
     * the following fields to allow the linked account activation to be submitted
     *
     * @var array
     */
    const MARKETPLACE_ACCOUNT_KYC_FIELDS = [
            Entity::PROMOTER_PAN,
            Entity::ADDRESS_PROOF_URL,
            Entity::PROMOTER_PAN_URL,
    ];
}
