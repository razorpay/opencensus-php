<?php

namespace RZP\Models\DeviceDetail;

use RZP\Constants\Country;

class Constants
{
    // onboarding source

    const EASY_ONBOARDING             = 'easy_onboarding';
    const ASSISTED_ONBOARDING         = 'assisted_onboarding';
    const PARTNER_ASSISTED_ONBOARDING = 'partner_assisted_onboarding';
    const PHANTOM_ONBOARDING  = 'phantom_onboarding';
    const UNBOUNCE              = 'unbounce';

    const CLIENT_IP       = 'ip';
    const G_CLICK_ID      = 'gclid';
    const SERVICE         = 'service';
    const SERVICE_PGOS    = 'pgos';
    const SERVICE_API     = 'api';
    const G_CLIENT_ID     = '_ga';

    const DEVICE                    = 'device';
    const TYPE                      = 'type';

    const ANDROID                   = 'android';
    const IOS                       = 'ios';

    const MOBILE_APP_SOURCES = [
        self::ANDROID,
        self::IOS
    ];
    const I18N_MY_SIGNUP = 'i18n_my_signup';

    const SINGAPORE_SIGNUP = 'sg_signup';

    const I18N_MY_LINKED_ACCOUNT_SIGNUP = "i18_my_linked_account_signup";

    const WORKFLOW_TYPE = 'workflow_type';

    const WORKFLOW_DETAILS = 'workflow_details';

    const PRODUCT  = "product";

    const FIELD_DATA  = "field_data";

    const START_VKYC  = "start_vkyc";

    const PLATFORM = "platform";
    const SALESEMAILID = "sales_email_id";

    const ALL_PLATFORM = "all"; // to be used later when obs changes are done

    const MOBILE = "mobile";

    const SIGNUP_SOURCE = "signup_source";

    const MODULAR_ONBOARDING = 'MODULAR_ONBOARDING';

    const RIZE_INCORPORATION = 'rize_incorporation';

    const PLATFORM_PG = 'pg';

    const VERSION_ID = 'version_id';

    const ORG_ID = 'org_id';

    const DEFAULT_VERSION = 'v1';

    const PRODUCT_PG_ONBOARDING = 'pg_onboarding';

    const CROSS_BORDER_ONBOARDING = 'cross_border_onboarding';

    const SUBMERCHANT_ONBOARDING = 'submerchant_onboarding';

    const PRODUCT_WORKFLOW_TYPE_TEMPLATE = '%s_workflow_type';

    const CURLEC_LINKED_ACCOUNT_ONBOARDING = "curlec_linked_account_onboarding";

    const PGOS_ENABLED_SIGNUP_CAMPAIGNS = [self::ASSISTED_ONBOARDING, self::PARTNER_ASSISTED_ONBOARDING, self::I18N_MY_SIGNUP, self::RIZE_INCORPORATION, self::SINGAPORE_SIGNUP] ;

    const CROSS_BORDER_FLOW = 'cross_border_flow';

    const SIGNUP_CAMPAIGN_ONBOARDING_MAPPING = [
        self::RIZE_INCORPORATION => [
            self::PLATFORM => self::PLATFORM_PG,
            self::PRODUCT => self::RIZE_INCORPORATION,
            self::WORKFLOW_TYPE => self::MODULAR_ONBOARDING
        ],
        self::I18N_MY_LINKED_ACCOUNT_SIGNUP => [
            self::PLATFORM => self::PLATFORM_PG,
            self::PRODUCT => self::CURLEC_LINKED_ACCOUNT_ONBOARDING,
            self::WORKFLOW_TYPE => self::MODULAR_ONBOARDING
        ],
    ];


    const COUNTRY_SIGNUP_CAMPAIGN_MAPPING = [
        'MY' =>  self::I18N_MY_LINKED_ACCOUNT_SIGNUP
    ];
}
