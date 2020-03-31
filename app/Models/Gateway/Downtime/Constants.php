<?php


namespace RZP\Models\Gateway\Downtime;


use RZP\Models\Admin\ConfigKey;

class Constants
{
    const SETTINGS_KEY  = ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2;

    const DOWNTIME_KEY  = 'DOWNTIME_CREATED';

    // In ratio to total payments
    const MAX_SINGLE_MERCHANT_CONTRIBUTION = 0.5;

    public static function getMaxSingleMerchantContribution()
    {
        return self::MAX_SINGLE_MERCHANT_CONTRIBUTION;
    }
}