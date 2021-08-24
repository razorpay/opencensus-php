<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail as MerchantDetail;

class Constants
{
    const EXPAND                   = 'expand';
    const MERCHANT                 = 'merchant';
    const QUESTIONS                = 'questions';
    const SUBMISSIONS              = 'submissions';
    const REJECTION_REASON         = 'rejection_reason';
    const NEEDS_CLARIFICATION_TEXT = 'needs_clarification_text';

    // Partners
    const ACTIVATION               = 'activation';
    const DEACTIVATION             = 'deactivation';

    // Rejection types
    const DISABLE_SETTLEMENT       = 'disable_settlement';
    const ENABLE_SETTLEMENT        = 'enable_settlement';
    const PROOF_OF_DELIVERY_MAIL   = 'proof_of_delivery_mail';
    const DISABLE_LIVE_MODE        = 'liveDisable';
    const FUNDS_ON_HOLD            = 'holdFunds';
    const SEND_MAIL                = 'sendRejectionEmailProof';

    /*
     * Need this map to map onboarding statuses to merchant request statuses to
     * ensure backward compatability with existing code till it isn't deprecated
     */
    const ONBOARDING_REQUEST_MAP = [
        MerchantDetail\Entity::PENDING  => Status::UNDER_REVIEW,
        MerchantDetail\Entity::APPROVED => Status::ACTIVATED,
        MerchantDetail\Entity::REJECTED => Status::REJECTED,
    ];

    const REJECTION_OPTION_MAP = [
        self::DISABLE_SETTLEMENT     => [self::DISABLE_LIVE_MODE, self::FUNDS_ON_HOLD],
        self::ENABLE_SETTLEMENT      => [self::DISABLE_LIVE_MODE],
        self::PROOF_OF_DELIVERY_MAIL => [self::DISABLE_LIVE_MODE, self::FUNDS_ON_HOLD, self::SEND_MAIL],
    ];

    public static $typeNamesMap = [
        Type::PRODUCT => [
            Feature\Constants::MARKETPLACE,
            Feature\Constants::VIRTUAL_ACCOUNTS,
            Feature\Constants::SUBSCRIPTIONS,
            Feature\Constants::QR_CODES
        ],
        Type::PARTNER => [
            self::ACTIVATION,
            self::DEACTIVATION,
        ],
    ];

    public static $instantlyActivatedProducts = [
        Feature\Constants::SUBSCRIPTIONS,
        Feature\Constants::MARKETPLACE,
        Feature\Constants::VIRTUAL_ACCOUNTS,
        Feature\Constants::QR_CODES
    ];


    /**
     * @param string $featureName
     *
     * @return bool
     */
    public static function isProductEnabledForInstantActivation(string $featureName): bool
    {

        return in_array($featureName, self::$instantlyActivatedProducts, true);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string          $featureName
     *
     * @return bool
     */
    public static function isAutoApproveFeatureRequest(Merchant\Entity $merchant, string $featureName): bool
    {
        return ($merchant->isActivated() === true) and
               (self::isProductEnabledForInstantActivation($featureName) === true);
    }

    public static function getRequestStatusForOnboardingStatus(string $onboardingStatus)
    {
        if (isset(self::ONBOARDING_REQUEST_MAP[$onboardingStatus]) === true)
        {
            return self::ONBOARDING_REQUEST_MAP[$onboardingStatus];
        }

        return null;
    }
}
