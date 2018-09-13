<?php

namespace RZP\Modules\Subscriptions;

use RZP\Models\Payment;
/**
 * Class Internal
 *
 * @package RZP\Modules\Subscriptions
 *
 * @property Subscription\Entity $subscription
 */
class Internal extends Base
{
    public function fetchSubscriptionInfo(array $input = [], $callback = false)
    {
        $subscription = $this->repo
                             ->subscription
                             ->findByPublicIdAndMerchant($input[Payment\Entity::SUBSCRIPTION_ID], $this->merchant);

        $subscription->setExternal(false);

        return $subscription;
    }
}
