<?php

namespace RZP\Http;

use App;

use RZP\Constants\Mode;
use RZP\Constants\Entity as E;
use RZP\Models\Base\QueryCache\Constants;

/**
* This trait overrides the
*/
trait OAuthCache
{

    /**
     * Gets the prefix to be used for the query cache key.
     * It is of the form "rememberable:v1:<entity>"
     * The version prefix is for deleting all cached values
     * for an entity corresponding to the version number. If the entity
     * attributes change, we need this to delete cached items corresponding
     * to this version.
     * The entity prefix is need to delete cached items corresponding to a
     * particular entity, if we want to bulk remove all keys corresponding
     * to a given entity.
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        $queryCacheVersion = $this->getQueryCacheVersion();

        $prefixArray = [
            Constants::QUERY_CACHE_PREFIX,
            $queryCacheVersion,
            E::AUTH_TOKEN,
        ];

        return implode(':', $prefixArray);
    }

    protected function getQueryCacheVersion(): string
    {
        return E::CACHED_ENTITIES[E::AUTH_TOKEN][Constants::VERSION] ??
            Constants::DEFAULT_QUERY_CACHE_VERSION;
    }

    public function getCacheTtl(): string
    {
        // Multiplying by 60 since cache put() expect ttl in seconds
        return (E::CACHED_ENTITIES[E::AUTH_TOKEN][Constants::TTL] ?? Constants::DEFAULT_QUERY_CACHE_TTL_MINS) * 60;
    }

    public function getCacheInfo($token): array
    {
        return [$this->getCacheTtl(), $this->getCacheKey($token)];
    }

    public function getCacheKey($token): string
    {
        return implode(':', [
            $this->getCachePrefix(),
            $this->getCacheTagsForToken($token)
        ]);
    }

    public function getCacheTagsForToken($token): string
    {
        return implode('_', [E::AUTH_TOKEN, hash('sha256', $token)]);
    }

    public function getCacheTagsForTokenId($id): string
    {
        return implode('_', [E::AUTH_TOKEN, self::ID, $id]);
    }

    public function getCacheDriverForTokens(): string
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? null;

        return ($mode === Mode::TEST) ? 'query_cache_test' : 'query_cache_live';
    }

    public function getCacheTags($token, $id): array
    {
        return [
            $this->getCacheTagsForToken($token),
            $this->getCacheTagsForTokenId($id)
        ];
    }
}
