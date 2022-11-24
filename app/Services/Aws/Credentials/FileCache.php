<?php

namespace RZP\Services\Aws\Credentials;

use Aws\CacheInterface;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use Illuminate\Cache\Repository;

/**
 * Implements file store as cache adapter to cache credentials received from EC2 meta data server.
 */
final class FileCache implements CacheInterface
{
    const NAMESPACE = 'aws:credentials:';

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function get($key): mixed
    {
        return $this->getCache()->get($this->getNamespacedKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = 0)
    {
        $this->getTrace()->debug(TraceCode::AWS_CREDS_CACHE_SET, compact('key', 'ttl'));

        //
        // Aws's sdk calls this method with ttl value(creds expiry time) in seconds.
        // (Ref: vendor/aws/aws-sdk-php/src/Credentials/CredentialProvider.php)
        //
        $ttl = (int) floor($ttl);   // ttl in seconds

        return $this->getCache()->set($this->getNamespacedKey($key), $value, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key)
    {
        return $this->getCache()->forget($this->getNamespacedKey($key));
    }

    /**
     * Returns namespaced key string
     * @return string
     */
    protected function getNamespacedKey($key)
    {
        return self::NAMESPACE . $key;
    }

    /**
     * @return Repository
     */
    protected function getCache(): Repository
    {
        return app('cache')->store('file');
    }

    /**
     * @return Logger
     */
    protected function getTrace(): Logger
    {
        return app('trace');
    }
}
