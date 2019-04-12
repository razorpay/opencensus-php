<?php

namespace RZP\Models\Terminal;

use App;
use Cache;

use RZP\Exception;
use RZP\Models\Base;
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

        foreach ($cachetagsArray as $cachetag)
        {
            $terminal->flushCache($cachetag);
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

    protected function getCachetags(Entity $terminal): array
    {
        $cachetagsArray = [];

        $cachetagsArray[] = Entity::getCacheTag($terminal->getId());

        $merchantIds = $terminal->merchants()->pluck(Entity::ID)->toArray();

        $merchantIds[] = $terminal->getMerchantId();

        $finalMerchantsIds = array_unique($merchantIds);

        foreach ($finalMerchantsIds as $merchantId)
        {
            $cachetagsArray[] = Entity::getCacheTag($merchantId);
        }

        return $cachetagsArray;
    }
}
