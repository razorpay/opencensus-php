<?php

namespace RZP\Services;

use Illuminate\Support\Facades\Redis;
use Predis\PredisException;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class ElasticCacheHelper
{
    protected $redis;

    public function __construct($app)
    {
        $this->redis = $app['redis']->connection('ec');
    }

    public function put($key, $value, $ttl)
    {
          $this->redis->setex(
            $key, (int) max(1, $ttl * 60), $this->serialize($value));
    }

    public function get($key)
    {
        $value = $this->redis->get($key);

        return ! is_null($value) ? $this->unserialize($value) : null;
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }

}
