<?php

namespace RZP\Models\Merchant;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;

class PhantomUtility
{
    public static function isPhantomOnBoardingWhitelistedForPartner(String $partnerId) : bool
    {
        $app = App::getFacadeRoot();

        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $app['config']->get('app.partner_submerchant_whitelabel_onboarding'),
        ];

        return (new Core)->isSplitzExperimentEnable($properties, 'enable');
    }

    public static function validatePhantomOnBoarding(String $partnerId) : bool
    {
        if (empty($partnerId) === true)
        {
            return false;
        }

        $isExpEnabled = self::isPhantomOnBoardingWhitelistedForPartner($partnerId);

        if ($isExpEnabled !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_WHITELABEL_ONBOARDING_EXP_NOT_ENABLED,
                null,
                ['partner_id' => $partnerId]
            );
        }

        return $isExpEnabled;
    }
}
