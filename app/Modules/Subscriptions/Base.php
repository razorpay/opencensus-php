<?php

namespace RZP\Modules\Subscriptions;

use RZP\Models\Merchant;
use RZP\Modules\Base as BaseModule;

abstract class Base extends BaseModule
{
    abstract public function fetchSubscriptionInfo(array $input, Merchant\Entity $merchant, $callback = false, $appTokenPresent = false);

    abstract public function paymentProcess(array $paymentPayload, string $mode);
}
