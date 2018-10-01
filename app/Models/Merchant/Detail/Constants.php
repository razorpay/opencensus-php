<?php

namespace RZP\Models\Merchant\Detail;

class Constants
{
    /**
     * The merchant will not be allowed to edit the fields listed in this array once they are activated. The admins
     * can however update these fields at any point in time.
     */
    const FIELDS_TO_DISABLE_POST_MERCHANT_ACTIVATION = [
        Entity::BUSINESS_CATEGORY,
        Entity::BUSINESS_SUBCATEGORY,
    ];
}
