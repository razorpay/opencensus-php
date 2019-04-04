<?php

namespace RZP\Models\Merchant\Methods;

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
     * @param Entity $methods
     * @throws Exception\RuntimeException
     */
    public function created(Entity $methods)
    {
        $this->validateEntity($methods);

        $methods->flushCache(Entity::getCacheTags($methods->getEntity(), $methods->getMerchantId()));
    }

    /**
     * Used to flush the cache on deletes.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<planID>_<type>
     *
     * @param Entity $methods
     * @throws Exception\RuntimeException
     */
    public function deleted(Entity $methods)
    {
        $this->validateEntity($methods);

        $methods->flushCache(Entity::getCacheTags($methods->getEntity(), $methods->getMerchantId()));
    }

    /**
     * Used to flush the cache on updates.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<planID>_<type>
     *
     * @param Entity $methods
     * @throws $methods\RuntimeException
     */
    public function updated($methods)
    {
        $this->validateEntity($methods);

        $methods->flushCache(Entity::getCacheTags($methods->getEntity(), $methods->getMerchantId()));
    }
}
