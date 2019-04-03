<?php

namespace RZP\Models\Pricing;

use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Models\Base\Observer as BaseObserver;

class Observer extends BaseObserver
{
    /**
     * Used to flush the cache on creates.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<planID>_<type>
     *
     * @param Entity $pricing
     */
    public function created(Entity $pricing)
    {
        $this->validateEntity($pricing);
        $pricing->flushCache(Entity::getCacheTags($pricing->getEntity(),
            $pricing->getPlanId(), $pricing->getType()));
    }

    /**
     * Used to flush the cache on deletes.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<planID>_<type>
     *
     * @param Entity $pricing
     */
    public function deleted(Entity $pricing)
    {
        $this->validateEntity($pricing);
        $pricing->flushCache(Entity::getCacheTags($pricing->getEntity(),
            $pricing->getPlanId(), $pricing->getType()));
    }

    /**
     * Used to flush the cache on updates.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<planID>_<type>
     *
     * @param Entity $pricing
     */
    public function updated($pricing)
    {
        $this->validateEntity($pricing);
        $pricing->flushCache(Entity::getCacheTags($pricing->getEntity(),
            $pricing->getPlanId(), $pricing->getType()));
    }
}
