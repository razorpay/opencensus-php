<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Merchant\Detail;

class Constants
{
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
}
