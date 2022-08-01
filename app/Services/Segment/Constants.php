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
        Merchant\Detail\Entity::ACTIVATION_STATUS,
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

    const EVENT_MILESTONE   = 'event_milestone';

    const ACTION_SOURCE                 = 'system_generated';
    const GOOGLE_UNIVERSAL_ANALYTICS    = 'Google Universal Analytics';
    const CLIENTID                      = 'clientId';

    const SELF_SERVE_ACTION = 'selfServeAction';

    const IS_WORKFLOW       = 'isWorkflow';

    const ACTION            = 'action';

    const OBJECT            = 'object';

    const SELF_SERVE        = 'Self Serve';

    const ONE_MONTH_POST    = '1 Month Post';

    const MTU               = 'mtu';

    const SIGNUP_SOURCE     = 'signup_source';

    const SUCCESS           = 'Success';

    const BE                = 'BE';
}
