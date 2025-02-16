<?php

namespace App\Splitz;

class SplitzConstants {
    public const PARTNERSHIPS_SUBMERCHANT_ONBOARDING_VIA_EASY = 'PARTNERSHIPS_SUBMERCHANT_ONBOARDING_VIA_EASY';
    public const UNIFIED_PG_REDIRECTION_ENABLED = 'UNIFIED_PG_REDIRECTION_ENABLED';
    public const CURLEC_REDIRECTION_ENABLED = 'CURLEC_REDIRECTION_ENABLED';
    public const DISABLE_EASY_REDIRECTION_FOR_BANKING = 'DISABLE_EASY_REDIRECTION_FOR_BANKING';

    public const DASHBOARD_HOMEPAGE_REDIRECTION_ENABLED = 'DASHBOARD_HOMEPAGE_REDIRECTION_ENABLED';

    /**
     * All experiments that uses rzp_ab_uuid cookie key to retrieve id and evaluates the experiment.
     */
    public const RzpAbUUIDExperiments = [
        self::PARTNERSHIPS_SUBMERCHANT_ONBOARDING_VIA_EASY,
    ];

    /**
     * All experiments that uses ab_user_id cookie key to retrieve id and evaluates the experiment.
     */
    public const AbUserIDExperiments = [
        self::UNIFIED_PG_REDIRECTION_ENABLED,
        self::DASHBOARD_HOMEPAGE_REDIRECTION_ENABLED,
    ];

    /**
     * All experiments that uses a unique id and evaluates the experiment.
     */
    public const UUIDExperiments = [
        self::CURLEC_REDIRECTION_ENABLED,
    ];

    /**
     * All experiments that uses logedin user id and evaluates the experiment.
     */
    public const UserIdExperiments = [
        self::DISABLE_EASY_REDIRECTION_FOR_BANKING,
    ];
}
