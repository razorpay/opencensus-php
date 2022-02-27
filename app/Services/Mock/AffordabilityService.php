<?php

namespace RZP\Services\Mock;

use RZP\Services\AffordabilityService as BaseAffordabilityService;

class AffordabilityService extends BaseAffordabilityService
{
    /**
     * @inheritDoc
     */
    public function invalidateCache(array $keys): bool
    {
        return true;
    }
}
