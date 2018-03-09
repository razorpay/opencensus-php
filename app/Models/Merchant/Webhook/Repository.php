<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
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

    public function findByMerchant($merchant)
    {
        $webhook = $this->newQuery()
                        ->merchantId($merchant->getId())
                        ->whereNull(Entity::ENTITY_TYPE)
                        ->whereNull(Entity::ENTITY_ID)
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

    public function findMultipleByApplicationIds(array $appIds)
    {
        $webhooks = $this->newQuery()
                         ->where(Entity::ENTITY_TYPE, Entity::APPLICATION)
                         ->whereIn(Entity::ENTITY_ID, $appIds)
                         ->get();

        return $webhooks;
    }

    protected function addQueryParamApplicationId(BuilderEx $query, array $params)
    {
        $entityTypeAttribute = $this->dbColumn(Entity::ENTITY_TYPE);

        $entityIdAttribute = $this->dbColumn(Entity::ENTITY_ID);

        $query->where($entityTypeAttribute, Entity::APPLICATION);

        $query->where($entityIdAttribute, $params[Entity::APPLICATION_ID]);
    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        $entityParams = [
            Entity::APPLICATION_ID,
            Entity::ENTITY_TYPE,
            Entity::ENTITY_ID,
        ];

        $entityParamsPresent = array_intersect_key($params, array_flip($entityParams));

        if (count($entityParamsPresent) === 0)
        {
            $query->whereNull(Entity::ENTITY_TYPE)
                  ->whereNull(Entity::ENTITY_ID);
        }

        return $query;
    }
}
