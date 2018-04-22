<?php

namespace RZP\Services\Aws;

use Aws\CacheInterface;
use Razorpay\Trace\Facades\Trace as TraceFacade;
use RZP\Trace\TraceCode;


class AwsCacheAdapter implements CacheInterface
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $store;

    public function __construct($store, $prefix = null)
    {
        $this->store = $store;

        $this->trace = TraceFacade::getFacadeRoot();

        $this->aws_metadata_ttl = env('AWS_METADATA_TTL', 300);
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        $value = $this->getCache()->get($key);

        $message = [
            "action" => "cache_get",
            "key" => $key,
            "value" => $value,
        ];

        $this->trace->debug(TraceCode::AWS_CACHE_GET, $message);

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
            "action" => "cache_set",
            "key" => $key,
            "value" => $value,
            "ttl" => $ttl
        ];

        $this->trace->debug(TraceCode::AWS_CACHE_SET, $message);

        $this->getCache()->put($key, $value, $this->convertTtl($this->aws_metadata_ttl));
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

        if ($minutes == 0) {
            return 1;
        } else {
            return $minutes;
        }
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