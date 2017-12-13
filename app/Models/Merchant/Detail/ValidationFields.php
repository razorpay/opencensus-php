<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Base;
use RZP\Models\Merchant\Detail;

class ValidationFields
{
    const DASHBOARD_FIELDS = [
            Entity::ADDRESS_PROOF_URL,
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_ACCOUNT_TYPE,
            Entity::BANK_BENEFICIARY_ADDRESS1,
            Entity::BANK_BENEFICIARY_CITY,
            Entity::BANK_BENEFICIARY_PIN,
            Entity::BANK_BENEFICIARY_STATE,
            Entity::BANK_BRANCH_IFSC,
            Entity::BUSINESS_DBA,
            Entity::BUSINESS_DOE,
            Entity::BUSINESS_INTERNATIONAL,
            Entity::BUSINESS_MODEL,
            Entity::BUSINESS_NAME,
            Entity::BUSINESS_OPERATION_ADDRESS,
            Entity::BUSINESS_OPERATION_CITY,
            Entity::BUSINESS_OPERATION_PIN,
            Entity::BUSINESS_OPERATION_STATE,
            Entity::BUSINESS_PAN_URL,
            Entity::BUSINESS_PAYMENTDETAILS,
            Entity::BUSINESS_PROOF_URL,
            Entity::BUSINESS_REGISTERED_ADDRESS,
            Entity::BUSINESS_REGISTERED_CITY,
            Entity::BUSINESS_REGISTERED_PIN,
            Entity::BUSINESS_REGISTERED_STATE,
            Entity::BUSINESS_TYPE,
            Entity::BUSINESS_WEBSITE,
            Entity::CONTACT_EMAIL,
            Entity::CONTACT_MOBILE,
            Entity::CONTACT_NAME,
            Entity::PROMOTER_ADDRESS_URL,
            Entity::PROMOTER_PAN_NAME,
            Entity::TRANSACTION_REPORT_EMAIL,
            Entity::TECHNICAL_SPOC_EMAIL,
            Entity::BUSINESS_SPOC_EMAIL,
            Entity::TRANSACTION_VALUE,
            Entity::TRANSACTION_VOLUME,
            Entity::WEBSITE_ABOUT,
            Entity::WEBSITE_CONTACT,
            Entity::WEBSITE_PRICING,
            Entity::WEBSITE_PRIVACY,
            Entity::WEBSITE_REFUND,
            Entity::WEBSITE_TERMS,
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
            Entity::BANK_ACCOUNT_TYPE,
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
