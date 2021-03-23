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
     */
    public function get($key)
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

        $this->getCache()->put($key, $value, $this->convertTtl($ttl));
    }

    /**
     * The AWS CacheInterface takes input in seconds, but the Laravel Cache classes use minutes. To support
     * this intelligently, we round up to one minute for any value less than 60 seconds, and round down to
     * the nearest whole minute for any value over one minute.
     *
     * @param $ttl
     * @return float|int
     */
    protected function convertTtl($ttl)
    {
        $minutes = floor($ttl / 60);

        if ($minutes == 0)
        {
            $minutes = 1;
        }

        return $minutes;
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

