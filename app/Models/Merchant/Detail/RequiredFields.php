<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Document\Type;

class RequiredFields
{
    const MERCHANT_DEFAULT_FIELDS = [
        //registered address
        Entity::BUSINESS_REGISTERED_ADDRESS,
        Entity::BUSINESS_REGISTERED_CITY,
        Entity::BUSINESS_REGISTERED_PIN,
        Entity::BUSINESS_REGISTERED_STATE,

        Entity::BUSINESS_TYPE,
        Entity::CONTACT_MOBILE,
        Entity::CONTACT_NAME,
        Entity::PROMOTER_PAN_NAME,
    ];

    const BUSINESS_PAN_FIELDS = [
        Entity::BUSINESS_PAN_URL,
    ];

    const BUSINESS_PROOF_DOCUMENT = [
        Type::BUSINESS_PROOF_URL,
    ];

    // KYC document fields
    const KYC_DOCUMENT_FIELDS = [
        Entity::BUSINESS_PAN_URL,
        Type::BUSINESS_PROOF_URL,
    ];

    //registered business default doc
    const REGISTERED_BUSINESS_FIELDS = [
        Entity::BUSINESS_DBA,
        Entity::BUSINESS_INTERNATIONAL,
        Entity::BUSINESS_NAME,

        //operational address related fields
        Entity::BUSINESS_OPERATION_ADDRESS,
        Entity::BUSINESS_OPERATION_CITY,
        Entity::BUSINESS_OPERATION_PIN,
        Entity::BUSINESS_OPERATION_STATE,
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

    const BOARD_RESOLUTION_FIELDS = [
        Type::MEMORANDUM_OF_ASSOCIATION,
        Type::ARTICLE_OF_ASSOCIATION,
        Type::BOARD_RESOLUTION,
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
