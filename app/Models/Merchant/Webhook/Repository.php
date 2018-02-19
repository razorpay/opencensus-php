<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'webhook';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
        Entity::ACTIVE      => 'sometimes|in:0,1',
    );

    public function getMethodsForMerchant(Merchant\Entity $merchant)
    {
        $methods = $this->find($merchant->getId());

        $methods->merchant()->associate($merchant);

        $merchant->setRelation('methods', $methods);

        return $methods;
    }

    public function findMultipleByMerchant($merchant)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->whereNull(Entity::ENTITY_TYPE)
                    ->whereNull(Entity::ENTITY_ID)
                    ->get();
    }

    public function findByMerchant($merchant)
    {
        $webhook = $this->newQuery()
                        ->merchantId($merchant->getId())
                        ->first();

        if ($webhook !== null)
        {
            $merchant->setRelation('webhook', $webhook);

            $webhook->merchant()->associate($merchant);
        }

        return $webhook;
    }

    public function findMultipleByMerchantAndEntityId(
        Merchant\Entity $merchant,
        string $entityId = null)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->get();
    }

    public function bumpFailureCount($webhook)
    {
        $webhook->bumpFailureCount();
        $webhook->saveOrFail();
    }

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->first();
    }

    public function resetFailureCount($webhook)
    {
        $webhook->resetFailureCount();
        $webhook->saveOrFail();
    }

    public function setLastSuccessfulAt($webhook)
    {
        $webhook->setLastSuccessfulAt();
        $webhook->saveOrFail();
    }
}