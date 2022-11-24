<?php


namespace RZP\Services\Aws\Credentials;



use Aws\CacheInterface;
use Razorpay\Trace\Facades\Trace;

use App\Trace\TraceCode;

class AwsCacheAdapter implements CacheInterface
{
    private $store;

    public function __construct(string $store, string $prefix = null)
    {
        $this->store = $store;
    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    public function get($key): mixed
    {
        $value = $this->getCache()->get($key);

        $message = [
            'action' => 'cache_get',
            'key'    => $key,
            'value'  => $value,
        ];

//        Trace::info(TraceCode::AWS_CACHE_GET, $message);

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        $this->getCache()->forget($key);
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl = 0)
    {
        $message = [
            'action' => 'cache_set',
            'key'    => $key,
            'value'  => $value,
            'ttl'    => $ttl,
        ];

//        Trace::info(TraceCode::AWS_CACHE_SET, $message);

        $this->getCache()->put($key, $value, $ttl);
    }

    /**
     * Returns the configured Laravel Cache Store
     *
     * @return mixed
     */
    protected function getCache()
    {
        return app('cache')->store($this->store);
    }
}

