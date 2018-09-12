<?php

namespace RZP\Modules\Subscriptions;

use Config;

use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;

class Factory
{
    public static function get()
    {
        return static::shouldUseExternalService() ? new External : new Internal;
    }

    protected static function shouldUseExternalService(): bool
    {
        $merchantFeatureCheck = true;

        $merchant = app('basicauth')->getMerchant();

        if ($merchant !== null)
        {
            $merchantFeatureCheck = $merchant->isFeatureEnabled(Feature\Constants::SUBSCRIPTION_AUTH_V2);
        }

        return ($merchantFeatureCheck === true);
    }
}
