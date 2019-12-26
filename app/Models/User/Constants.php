<?php

namespace RZP\Models\User;

class Constants
{
    const CTA           = 'cta';

    const WEBSITE       = 'website';

    const FC_SOURCE     = 'fc_source';

    const LC_SOURCE     = 'lc_source';

    const UTM_SOURCE    = "utm_source";

    const UTM_CAMPAIGN  = "utm_campaign";

    const UTM_MEDIUM    = "utm_medium";

    const UTM_TERM      = "utm_term";

    const UTM_CONTENT   = "utm_content";

    const TIMESTAMP     = "timestamp";

    const ATTRIBUTIONS  = "attributions";

    public static $attributionList = [
        self::UTM_SOURCE,
        self::UTM_CAMPAIGN,
        self::UTM_MEDIUM,
        self::UTM_TERM,
        self::UTM_CONTENT,
        self::TIMESTAMP,
    ];

    const PASSWORD_RESET_TOKEN_EXPIRY_TIME =  3600; //1 hour

    const LINKED_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME =  86400; //24 hours

    const SUBMERCHANT_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME =  86400; //24 hours

    const LOCK   = 'lock';
    const UNLOCK = 'unlock';
}
