<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Feature;
use RZP\Models\Merchant\Partner;
use RZP\Models\Merchant\Detail as MerchantDetail;

class Constants
{
    const EXPAND                   = 'expand';
    const MERCHANT                 = 'merchant';
    const QUESTIONS                = 'questions';
    const SUBMISSIONS              = 'submissions';
    const REJECTION_REASON         = 'rejection_reason';
    const NEEDS_CLARIFICATION_TEXT = 'needs_clarification_text';

    /*
     * Need this map to map onboarding statuses to merchant request statuses to
     * ensure backward compatability with existing code till it isn't deprecated
     */
    const ONBOARDING_REQUEST_MAP = [
        MerchantDetail\Entity::PENDING  => Status::UNDER_REVIEW,
        MerchantDetail\Entity::APPROVED => Status::ACTIVATED,
        MerchantDetail\Entity::REJECTED => Status::REJECTED,
    ];

    public static $names = [
        // Product activation requests
        Feature\Constants::MARKETPLACE,
        Feature\Constants::VIRTUAL_ACCOUNTS,
        Feature\Constants::SUBSCRIPTIONS,

        // Partner activation requests
        Partner\Constants::BANK,
        Partner\Constants::RESELLER,
        Partner\Constants::AGGREGATOR,
        Partner\Constants::FULLY_MANAGED,
        Partner\Constants::PURE_PLATFORM,
    ];

    public static function getRequestStatusForOnboardingStatus(string $onboardingStatus)
    {
        if (isset(self::ONBOARDING_REQUEST_MAP[$onboardingStatus]) === true)
        {
            return self::ONBOARDING_REQUEST_MAP[$onboardingStatus];
        }

        return null;
    }
}
