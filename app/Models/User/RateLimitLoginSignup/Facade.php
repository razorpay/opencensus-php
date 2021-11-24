<?php

namespace RZP\Models\User\RateLimitLoginSignup;

use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * @method static int incrementAndGetKeyCount(string $key, string $suffix, int $ttl, string $traceCode, string $errorCode, string $errorDescription)
 * @method static validateKeyLimitExceeded(string $key, string $suffix, int $ttl, int $threshold)
 * @method static resetKey(string $key, string $suffix)
 */
class Facade extends BaseFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'rate_limit_login_signup';
    }
}
