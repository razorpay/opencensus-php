<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Merchant\Detail;

class Constants
{
    // request constants
    const RELATIONSHIP = 'relationship';
    const DIRECTOR     = 'director';
    const EXECUTIVE    = 'executive';
    const PRIMARY      = 'primary';
    const SECONDARY    = 'secondary';
    const ADDRESSES    = 'addresses';
    const RESIDENTIAL  = 'residential';
    const STREET       = 'street';
    const CITY         = 'city';
    const STATE        = 'state';
    const POSTAL_CODE  = 'postal_code';
    const COUNTRY      = 'country';
    const KYC          = 'kyc';
    const PAN          = 'pan';
    const PHONE        = 'phone';
    const VERIFICATION_TYPE = 'verification_type';
    const VERIFICATION_METADATA  = 'verification_metadata';

    const MERCHANT_DETAILS_COMMON_FIELDS = [
        Entity::MERCHANT_ID               => Detail\Entity::MERCHANT_ID,
        Entity::NAME                      => Detail\Entity::PROMOTER_PAN_NAME,
        Entity::POI_IDENTIFICATION_NUMBER => Detail\Entity::PROMOTER_PAN,
        Entity::POI_STATUS                => Detail\Entity::POI_VERIFICATION_STATUS,
        Entity::PAN_DOC_STATUS            => Detail\Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS,
        Entity::POA_STATUS                => Detail\Entity::POA_VERIFICATION_STATUS,
    ];

    const MERCHANT_DETAILS_COMMON_EDITABLE_FIELDS = [
        Entity::NAME                      => Detail\Entity::PROMOTER_PAN_NAME,
        Entity::POI_IDENTIFICATION_NUMBER => Detail\Entity::PROMOTER_PAN,
    ];

    const MERCHANT_DETAILS_STAKEHOLDER_MAPPING = [
        Detail\Entity::PROMOTER_PAN_NAME => Entity::NAME,
        Detail\Entity::PROMOTER_PAN      => Entity::POI_IDENTIFICATION_NUMBER
    ];

}
