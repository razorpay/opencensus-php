<?php

namespace RZP\Models\Plan\Subscription\Addon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Plan\Subscription;

class Repository extends Base\Repository
{
    /**
     * Query parameter to include soft delted results.
     */
    const DELETED = 'deleted';

    protected $entity = 'addon';

    protected $entityFetchParamRules = [
        Entity::SUBSCRIPTION_ID     => 'filled|string|public_id',
        Entity::INVOICE_ID          => 'filled|string|public_id',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'filled|string|size:14',
        self::DELETED               => 'sometimes|boolean',
    ];

    protected $signedIds = [
        Entity::SUBSCRIPTION_ID,
        Entity::INVOICE_ID,
    ];

    protected $expands = [
        Entity::ITEM
    ];

    public function getUnusedAddonsForSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->whereNull(Entity::INVOICE_ID)
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->with(Constants\Entity::ITEM)
                    ->get();
    }

    public function getAddonsForSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->with(Constants\Entity::ITEM)
                    ->get();
    }

    protected function addQueryParamDeleted($query, $params)
    {
        $includeSoftDeleted = (bool) $params[self::DELETED];

        if ($includeSoftDeleted === true)
        {
            $query->withTrashed();
        }
    }
}
