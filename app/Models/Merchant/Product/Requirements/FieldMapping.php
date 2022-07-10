<?php

namespace RZP\Models\Merchant\Product\Requirements;

use RZP\Constants\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Stakeholder;

class FieldMapping
{
    const FIELD_MAPPING = [
        Entity::MERCHANT    => [
            Detail\Entity::BANK_ACCOUNT_NUMBER            => 'settlements.account_number',
            Detail\Entity::BANK_ACCOUNT_NAME              => 'settlements.beneficiary_name',
            Detail\Entity::BANK_BRANCH_IFSC               => 'settlements.ifsc_code',
            Detail\Entity::BUSINESS_TYPE                  => 'business_type',
            Detail\Entity::BUSINESS_CATEGORY              => 'profile.category',
            Detail\Entity::BUSINESS_SUBCATEGORY           => 'profile.subcategory',
            Detail\Entity::BUSINESS_REGISTERED_STATE      => 'profile.address.registered.state',
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS    => 'profile.address.registered.street1',
            Detail\Entity::BUSINESS_REGISTERED_ADDRESS_L2 => 'profile.address.registered.street2',
            Detail\Entity::BUSINESS_REGISTERED_PIN        => 'profile.address.registered.postal_code',
            Detail\Entity::BUSINESS_REGISTERED_COUNTRY    => 'profile.address.registered.country',
            Detail\Entity::BUSINESS_REGISTERED_CITY       => 'profile.address.registered.city',
            Detail\Entity::BUSINESS_OPERATION_ADDRESS     => 'profile.address.operation.street1',
            Detail\Entity::BUSINESS_OPERATION_ADDRESS_L2  => 'profile.address.operation.street2',
            Detail\Entity::BUSINESS_OPERATION_STATE       => 'profile.address.operation.state',
            Detail\Entity::BUSINESS_OPERATION_PIN         => 'profile.address.operation.postal_code',
            Detail\Entity::BUSINESS_OPERATION_COUNTRY     => 'profile.address.operation.country',
            Detail\Entity::BUSINESS_OPERATION_CITY        => 'profile.address.operation.city',
            Detail\Entity::COMPANY_PAN                    => 'legal_info.pan',
            Detail\Entity::COMPANY_CIN                    => 'legal_info.cin',
            Detail\Entity::GSTIN                          => 'legal_info.gst',
            Detail\Entity::BUSINESS_DBA                   => 'customer_facing_business_name',
            Detail\Entity::CONTACT_EMAIL                  => 'email',
            Detail\Entity::CONTACT_MOBILE                 => 'phone',
            Detail\Entity::BUSINESS_NAME                  => 'legal_business_name',
            Detail\Entity::CONTACT_NAME                   => 'contact_name',
        ],
        Entity::STAKEHOLDER => [
            Stakeholder\Entity::NAME                      => 'name',
            Stakeholder\Entity::POI_IDENTIFICATION_NUMBER => 'kyc.pan'
        ],
        Entity::MERCHANT_OTP_VERIFICATION_LOGS => [
            Product\Util\Constants::CONTACT_MOBILE              =>'otp.contact_mobile',
            Product\Util\Constants::REFERENCE_NUMBER            =>'otp.external_reference_number',
            Product\Util\Constants::OTP_SUBMISSION_TIMESTAMP    =>'otp.otp_submission_timestamp',
            Product\Util\Constants::OTP_VERIFICATION_TIMESTAMP  =>'otp.otp_verification_timestamp'
        ]
    ];
}
