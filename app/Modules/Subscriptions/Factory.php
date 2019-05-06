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
        // yaaay
        return true;
    }
}
