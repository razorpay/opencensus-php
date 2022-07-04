<?php

namespace RZP\Models\PaymentLink\CustomDomain;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Helper
{
    const MERCHANT_ID_KEY   = "merchant_id";
    const DOMAIN_NAME_KEY   = "domain_name";

    /**
     * @param string $domain
     *
     * @return string
     */
    public static function getDomainCacheKey(string $domain): string
    {
        $prefix = Config::get('services.custom_domain_service.cache.prefix');

        return $prefix . ":DOMAIN:" . $domain;
    }

    /**
     * @return mixed
     */
    public static function getDomainCacheTTL()
    {
        return Config::get('services.custom_domain_service.cache.ttl');
    }

    /**
     * @param string $domain
     *
     * @return string|null
     */
    public static function getDomainFromCache(string $domain): ?string
    {
        $cacheKey = self::getDomainCacheKey($domain);

        return Cache::get($cacheKey);
    }

    /**
     * @param array $domainData
     *
     * @return void
     */
    public static function cacheDomain(array $domainData): void
    {
        $cacheKey = self::getDomainCacheKey($domainData[self::DOMAIN_NAME_KEY]);

        $ttl = self::getDomainCacheTTL();

        Cache::put($cacheKey, $domainData[self::MERCHANT_ID_KEY], $ttl);
    }
}
