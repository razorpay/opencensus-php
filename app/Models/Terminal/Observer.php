<?php

namespace RZP\Models\Terminal;

use App;
use Cache;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Observer as BaseObserver;

class Observer extends BaseObserver
{
    public function created(Entity $terminal)
    {
         $this->validateEntity($terminal);

         $this->flushCache($terminal);

    }

    public function updated($terminal)
    {
        $this->validateEntity($terminal);

        $this->flushCache($terminal);
    }

    protected function flushCache($terminal)
    {
        $cachetagsArray = $this->getCacheTags($terminal);

        $merchantRelationArray = $this->getMerchantRelationKeys($terminal);

        foreach ($cachetagsArray as $cachetag)
        {
            $terminal->flushCache($cachetag);
        }

        foreach ($merchantRelationArray as $merchantRelation)
        {
            Cache::delete($merchantRelation);
        }
    }

    protected function validateEntity($entity)
    {
        if (($entity instanceof Entity) === false)
        {
            throw new Exception\RuntimeException('Entity should be instance of Terminal Entity',
                [
                    'entity' => $entity
                ]);
        }
    }

    protected function getMerchantRelationKeys(Entity $terminal): array
    {
        $merchantTagArray = [];

        $merchantIds = $terminal->merchants()->pluck(Entity::ID)->toArray();

        $merchantIds[] = $terminal->getMerchantId();

        $finalMerchantsIds = array_unique($merchantIds);

        foreach ($finalMerchantsIds as $merchantId)
        {
            $merchantTagArray[] = Entity::getMerchantRelationKey($merchantId);
        }

        return $merchantTagArray;
    }

    protected function getCachetags(Entity $terminal): array
    {
        $cachetagsArray = [];

        $merchantIds = $terminal->merchants()->pluck(Entity::ID)->toArray();

        $merchantIds[] = $terminal->getMerchantId();

        $finalMerchantsIds = array_unique($merchantIds);

        foreach ($finalMerchantsIds as $merchantId)
        {
            $cachetagsArray[] =  Entity::getCacheTag($merchantId);
        }

        return $cachetagsArray;
    }
}
