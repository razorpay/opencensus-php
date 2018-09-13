<?php

namespace RZP\Modules\Subscriptions;

use RZP\Modules\Base as BaseModule;

abstract class Base extends BaseModule
{
    abstract public function fetchSubscriptionInfo(array $input, $callback = false);
}
