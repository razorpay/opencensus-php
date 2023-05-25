<?php

namespace RZP\Services\Mock;

use RZP\Services\AffordabilityService as BaseAffordabilityService;

class AffordabilityService extends BaseAffordabilityService
{
    /**
     * @inheritDoc
     */
    public function invalidateCache(array $keys, string $merchantId = null, bool $InvalidateTerminalCache = false, bool $InvalidateMerchantMethodsCache = false, string $terminalMethod = null): bool
    {
        return true;
    }
}
