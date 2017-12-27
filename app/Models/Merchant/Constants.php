<?php

namespace RZP\Models\Merchant;

/**
 * General constants for Merchant Model.
 */
final class Constants
{
    const INDIVIDUAL                              = 'individual';
    const CONTACT                                 = 'contact';
    const TIMESTAMP                               = 'timestamp';
    const REF                                     = 'ref';
    const DATE                                    = 'date';

    /**
     * Step Map gives information on attributes filled by merchant Step wise.
     * this is used to let merchant know what all the steps are finished and
     * can continue from where merchant left the activation form.
     */
    const STEP_MAP = [
        Detail\Entity::CONTACT_NAME                => 1,
        Detail\Entity::CONTACT_EMAIL               => 1,
        Detail\Entity::TRANSACTION_REPORT_EMAIL    => 1,
        Detail\Entity::TECHNICAL_SPOC_EMAIL        => 1,
        Detail\Entity::BUSINESS_SPOC_EMAIL         => 1,
        Detail\Entity::CONTACT_MOBILE              => 1,
        Detail\Entity::CONTACT_LANDLINE            => 1,

        Detail\Entity::BUSINESS_TYPE               => 2,
        Detail\Entity::BUSINESS_NAME               => 2,
        Detail\Entity::BUSINESS_DBA                => 2,
        Detail\Entity::BUSINESS_INTERNATIONAL      => 2,
        Detail\Entity::BUSINESS_PAYMENTDETAILS     => 2,
        Detail\Entity::BUSINESS_MODEL              => 2,
        Detail\Entity::BUSINESS_REGISTERED_ADDRESS => 2,
        Detail\Entity::BUSINESS_REGISTERED_STATE   => 2,
        Detail\Entity::BUSINESS_REGISTERED_CITY    => 2,
        Detail\Entity::BUSINESS_REGISTERED_PIN     => 2,
        Detail\Entity::BUSINESS_OPERATION_ADDRESS  => 2,
        Detail\Entity::BUSINESS_OPERATION_STATE    => 2,
        Detail\Entity::BUSINESS_OPERATION_CITY     => 2,
        Detail\Entity::BUSINESS_OPERATION_PIN      => 2,
        Detail\Entity::BUSINESS_DOE                => 2,
        Detail\Entity::TRANSACTION_VOLUME          => 2,
        Detail\Entity::TRANSACTION_VALUE           => 2,
        Detail\Entity::GSTIN                       => 2,
        Detail\Entity::P_GSTIN                     => 2,
        Detail\Entity::PROMOTER_PAN                => 2,
        Detail\Entity::PROMOTER_PAN_NAME           => 2,

        Detail\Entity::BUSINESS_WEBSITE            => 3,
        Detail\Entity::WEBSITE_ABOUT               => 3,
        Detail\Entity::WEBSITE_CONTACT             => 3,
        Detail\Entity::WEBSITE_PRIVACY             => 3,
        Detail\Entity::WEBSITE_TERMS               => 3,
        Detail\Entity::WEBSITE_REFUND              => 3,
        Detail\Entity::WEBSITE_PRICING             => 3,

        Detail\Entity::BANK_BRANCH_IFSC            => 4,
        Detail\Entity::BANK_ACCOUNT_NUMBER         => 4,
        Detail\Entity::BANK_ACCOUNT_TYPE           => 4,
        Detail\Entity::BANK_ACCOUNT_NAME           => 4,
        Detail\Entity::BANK_BENEFICIARY_ADDRESS1   => 4,
        Detail\Entity::BANK_BENEFICIARY_ADDRESS2   => 4,
        Detail\Entity::BANK_BENEFICIARY_ADDRESS3   => 4,
        Detail\Entity::BANK_BENEFICIARY_CITY       => 4,
        Detail\Entity::BANK_BENEFICIARY_STATE      => 4,
        Detail\Entity::BANK_BENEFICIARY_PIN        => 4,

        Detail\Entity::BUSINESS_PROOF_URL          => 5,
        Detail\Entity::BUSINESS_PAN_URL            => 5,
        Detail\Entity::ADDRESS_PROOF_URL           => 5,
        Detail\Entity::PROMOTER_ADDRESS_URL        => 5,
    ];

    const STEP_MAP_ACCOUNT = [
        Detail\Entity::BUSINESS_TYPE               => 1,
        Detail\Entity::BUSINESS_NAME               => 1,
        Detail\Entity::COMPANY_PAN                 => 1,
        Detail\Entity::PROMOTER_PAN                => 1,

        Detail\Entity::BANK_BRANCH_IFSC            => 2,
        Detail\Entity::BANK_ACCOUNT_NUMBER         => 2,
        Detail\Entity::BANK_ACCOUNT_TYPE           => 2,
        Detail\Entity::BANK_ACCOUNT_NAME           => 2,

        Detail\Entity::ADDRESS_PROOF_URL           => 3,
        Detail\Entity::PROMOTER_PAN_URL            => 3,
    ];

    const UPLOAD_KEYS = [
        Detail\Entity::BUSINESS_PROOF_URL   => 'business_proof',
        Detail\Entity::BUSINESS_PAN_URL     => 'business_pan_proof',
        Detail\Entity::ADDRESS_PROOF_URL    => 'address_proof',
        Detail\Entity::PROMOTER_ADDRESS_URL => 'promoter_address_proof',
    ];

    const UPLOAD_KEYS_ACCOUNT = [
        Detail\Entity::ADDRESS_PROOF_URL    => 'address_proof',
        Detail\Entity::PROMOTER_PAN_URL     => 'promoter_pan_proof',
    ];

    const PRE_SIGNUP_FIELDS = [
        Detail\Entity::BUSINESS_TYPE,
        Detail\Entity::TRANSACTION_VOLUME,
        Detail\Entity::ROLE,
        Detail\Entity::DEPARTMENT,
        Detail\Entity::CONTACT_NAME,
        Detail\Entity::BUSINESS_NAME,
        Detail\Entity::CONTACT_MOBILE,
    ];
}
