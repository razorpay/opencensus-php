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
        string $entityId = null): Base\PublicCollection
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

    public function findMultipleByApplicationIds(array $appIds): Base\PublicCollection
    {
        $webhooks = $this->newQuery()
                         ->where(Entity::ENTITY_TYPE, Entity::APPLICATION)
                         ->whereIn(Entity::ENTITY_ID, $appIds)
                         ->get();

        return $webhooks;
    }

    public function getWebhooksByEventEnabled(string $event): Base\PublicCollection
    {
        $position = Event::getBitPosition($event);

        // This is a number of the form 100000.. in binary. The idea is that
        // only the bit corresponding to the event being queried is set. When
        // AND-ed with the webhook event value, the result will be the same
        // number if the bit is set, or else 0.
        $bitComparator = (1 << ($position - 1));

        //
        // SELECT *
        // FROM `webhooks`
        // WHERE `active` = 1
        //   AND events & 1024 = 1024
        // -- Here the event queried has position 10, so comparator is 1024
        //
        $query = $this->newQuery()
                      ->where(Entity::ACTIVE, true)
                      ->whereRaw(Entity::EVENTS . ' & ' . $bitComparator . ' = ' . $bitComparator);

        return $query->get();
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

    /**
     * {@inheritDoc}
     *
     * Method overridden to dual write to stork's webhook module.
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function saveOrFail($entity, array $options = array())
    {
        // This check must be before parent's method call because later mutates var like exists.
        $shouldUpsertStork = (($entity->exists === false) or
            ($entity->isDirty(Entity::STORK_UPDATEABLE_FIELDS) === true));

        parent::saveOrFail($entity, $options);

        // Intentionally not within transaction for initial phase.
        if ($shouldUpsertStork === true)
        {
            try
            {
                (new Stork)->upsert($entity);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }
    }
}
