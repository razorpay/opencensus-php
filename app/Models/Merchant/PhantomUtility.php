<?php

namespace RZP\Models\Merchant;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Partner\Constants as PartnerConstants;

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

    public static function validatePhantomOnboardingForPurePlatformPartners(String $partnerId) : bool
    {
        $isExpEnabled = self::isPhantomOnboardingWhitelistedForPurePlatformPartner($partnerId);

        if ($isExpEnabled !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_OAUTH_ONBOARDING_EXP_NOT_ENABLED,
                null,
                ['partner_id' => $partnerId]
            );
        }

        return $isExpEnabled;
    }

    private static function isPhantomOnboardingWhitelistedForPurePlatformPartner(String $partnerId) : bool
    {
        $app = App::getFacadeRoot();

        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $app['config']->get('app.partner_submerchant_oauth_onboarding')
        ];

        return (new Core())->isSplitzExperimentEnable($properties, 'enable');
    }

    public static function checkIfPhantomOnBoardingFlow(array &$input) : bool
    {
        if ((isset($input[Constants::SOURCE]) === false) or
            (empty($input[Constants::SOURCE]) === true))
        {
            return false;
        }

        $isPhantomFlowEnabled = $input[Constants::SOURCE] == PartnerConstants::PHANTOM;

        unset($input[Constants::SOURCE]);

        return $isPhantomFlowEnabled;
    }

    public static function checkAndSetContextForPhantomSource(array &$input) : void
    {
        if ((isset($input[Constants::SOURCE]) === false) or
            (empty($input[Constants::SOURCE]) === true))
        {
            return;
        }

        $isPhantomFlow = $input[Constants::SOURCE] == PartnerConstants::PHANTOM;

        \Request::instance()->request->add([Constants::PHANTOM_ONBOARDING_FLOW_ENABLED => $isPhantomFlow]);

        unset($input[Constants::SOURCE]);
    }

}
