<?php

namespace RZP\Modules\Subscriptions;

use RZP\Models\Payment;
use RZP\Models\Merchant;
/**
 * Class Internal
 *
 * @package RZP\Modules\Subscriptions
 *
 * @property Subscription\Entity $subscription
 */
class Internal extends Base
{
    public function fetchSubscriptionInfo(array $input = [], Merchant\Entity $merchant, $callback = false, $appTokenPresent = false)
    {
        $subscription = $this->repo
                             ->subscription
                             ->findByPublicIdAndMerchant($input[Payment\Entity::SUBSCRIPTION_ID], $merchant);

        $subscription->setExternal(false);

        return $subscription;
    }
}
