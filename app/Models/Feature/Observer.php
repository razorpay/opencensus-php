<?php

namespace RZP\Models\Feature;

use RZP\Models\Base\Observer as BaseObserver;

class Observer extends BaseObserver
{
    /**
     * Used to flush the cache on creates.
     *
     * We flush the cache by deleting all keys with the tag
     * feature_<entity_type>_<entity_id>
     *
     * @param Entity $feature
     */
    public function created(Entity $feature)
    {
        $this->validateEntity($feature);

        //
        // Multiple features are cached based on the relation to a Merchant/Application
        // When a new feature is added, the merchant/application level cache must be invalidated
        //
        $feature->flushCache($feature::getCacheTagsForEntities($feature->getEntityType(), $feature->getEntityId()));

        $feature->flushCache($feature::getCacheTagsForNames($feature->getEntityType(), $feature->getEntityId()));
    }

    /**
     * Used to flush the cache on deletes.
     *
     * We flush the cache by deleting all keys with the tag
     * feature_<entity_type>_<entity_id>
     *
     * @param Entity $feature
     */
    public function deleted($feature)
    {
        $this->validateEntity($feature);

        $feature->flushCache($feature::getCacheTagsForEntities($feature->getEntityType(), $feature->getEntityId()));

        $feature->flushCache($feature::getCacheTagsForNames($feature->getEntityType(), $feature->getEntityId()));
    }

    /**
     * Used to flush the cache on updates.
     *
     * We flush the cache by deleting all keys with the tag
     * feature_<entity_type>_<entity_id>
     *
     * @param Entity $feature
     */
    public function updated($feature)
    {
        $this->validateEntity($feature);

        $feature->flushCache($feature::getCacheTagsForEntities($feature->getEntityType(), $feature->getEntityId()));

        $feature->flushCache($feature::getCacheTagsForNames($feature->getEntityType(), $feature->getEntityId()));
    }
}
