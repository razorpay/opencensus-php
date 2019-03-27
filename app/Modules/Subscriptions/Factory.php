<?php

namespace RZP\Modules\Subscriptions;

use Route;
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

        if (Route::currentRouteName() === 'payment_create_subscriptions')
        {
            // Used for subscription service charges, eg. test_charge, manual_charge, cron, etc.
           return true;
        }

        $merchant = app('basicauth')->getMerchant();

        if ($merchant !== null)
        {
            // Used for subscription authentication payment
            $response = app()->razorx->getTreatment($merchant->getId(), 'auth_flow_redirect_to_subserv', app()['rzp.mode']);

            if (strtolower($response) === 'on')
            {
                return true;
            }
        }

        return false;
    }
}
