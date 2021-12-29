<?php


namespace RZP\Services\Segment;

use RZP\Models\Merchant;

class Constants
{
    const SEGMENT_EVENT_CATEGORY = "Backend - offline - Segment";

    const COMMON_MERCHANT_DETAIL_PROPERTIES = [
        Merchant\Detail\Entity::POI_VERIFICATION_STATUS,
        Merchant\Detail\Entity::POA_VERIFICATION_STATUS,
        Merchant\Detail\Entity::GSTIN_VERIFICATION_STATUS,
        Merchant\Detail\Entity::CIN_VERIFICATION_STATUS,
        Merchant\Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS,
        Merchant\Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS,
        Merchant\Detail\Entity::MSME_DOC_VERIFICATION_STATUS,
        Merchant\Detail\Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS,
        Merchant\Detail\Entity::BUSINESS_CATEGORY,
        Merchant\Detail\Entity::BUSINESS_SUBCATEGORY,
        Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE,
        Merchant\Detail\Entity::BUSINESS_TYPE,
        Merchant\Detail\Entity::BUSINESS_DBA,
    ];

    //common event properties
    const SOURCE        = 'source';
    const MODE          = 'mode';
    const USER_ROLE     = 'user_role';
    const USER_ID       = 'user_id';
    const INTEGRATIONS  = 'integrations';
    const APPSFLYER     = 'AppsFlyer';
    const APPSFLYERID   = 'appsFlyerId';

    const REGULAR_MERCHANT = "regular_merchant";
}
