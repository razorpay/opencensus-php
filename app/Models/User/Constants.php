<?php

namespace RZP\Models\User;

class Constants
{
    const CTA           = 'cta';

    const WEBSITE       = 'website';

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
}

