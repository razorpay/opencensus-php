<?php

namespace RZP\Models\Merchant\Request;

use Elasticsearch\Endpoints\Cluster\State;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Feature\Constants as FeatureConstants;

class Constants
{
    const EXPAND   = 'expand';
    const MERCHANT = 'merchant';

    // Need this map to map onboarding statuses to marchant request statuses to
    // ensure backward compatability with existing code till it isn't deprecated
    const ONBOARDING_REQUEST_MAP = [
        MerchantDetail\Entity::PENDING => Status::UNDER_REVIEW,
        MerchantDetail\Entity::APPROVED => Status::ACTIVATED,
        MerchantDetail\Entity::REJECTED => Status::REJECTED
    ];

    public static function mapOnboardingStatusToRequestStatus(string $onboardingStatus)
    {
        if (isset(self::ONBOARDING_REQUEST_MAP[$onboardingStatus]) === true)
        {
            return self::ONBOARDING_REQUEST_MAP[$onboardingStatus];
        }

        return null;
    }
}
