<?php

namespace RZP\Services;

use Illuminate\Support\Facades\Redis;
use Predis\PredisException;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class RedisDualWrite
{
    protected $trace;

    protected $shouldReadRedisLabs;

    const PREFIX = 'mutex:ec';

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $config = $app['config']->get('applications.redisdualwrite');

        $this->shouldReadRedisLabs = $config['redislab_cache_read'];
    }

    public function set($key, $value, $expireResolution, $expireTTL, $flag)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->set($key, $value, $expireResolution, $expireTTL, $flag);

            $redisLabsResponse = $redis->set($key, $value, $expireResolution, $expireTTL, $flag);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['key' => $key]);

            $redis->del($key);

            $redisEc->del($key);

            throw $e;
        }
    }

    public function get($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->get($key);

            $redisLabsResponse = $redis->get($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }

        return $response;
    }

    public function ttl($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->ttl($key);

            $redisLabsResponse = $redis->ttl($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_TTL_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }

        return $response;

    }


    public function del($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        list($data, $ttl) = $this->backup($key);

        try
        {
            $redisEcResponse = $redisEc->del($key);

            $redisLabsResponse = $redis->del($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_DELETE_ERROR,
                ['key' => $key]);

            $this->restore($key, $data, $ttl);

            throw $e;
        }
    }

    public function hGetAll($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->hGetAll($key);

            $redisLabsResponse = $redis->hGetAll($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }

        return $response;
    }

    protected function backup($key)
    {
        $data = null;
        $ttl = null;

        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $data = $redis->get($key);
            $ttl  =  $redis->ttl($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_BACKUP_ERROR,
                ['key' => $key]);

        }

        if ($data === null)
        {
            $data = $redisEc->get($key);
            $ttl =  $redisEc->ttl($key);
        }

        return [$data, $ttl];
    }

    protected function hBackup($key, $field)
    {
        $data = null;
        $ttl = null;

        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $data = $redis->hGet($key, $field);
            $ttl  =  $redis->ttl($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_BACKUP_ERROR,
                ['key' => $key]);

        }

        if ($data === null)
        {
            $data = $redisEc->hGet($key, $field);
            $ttl =  $redisEc->ttl($key);
        }

        return [$data, $ttl];
    }

    protected function restore($key, $data, $ttl)
    {
        $this->set($key, $data, 'ex', $ttl, 'nx');
    }

    public function hSet($key, $field, $value)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->hSet($key, $field, $value);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redis->hSet($key, $field, $value);
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            $redisEc->hdel($key, $value);

            $redis->hdel($key, $value);

            throw $e;
        }
    }

    public function hGet($key, $field)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->hGet($key, $field);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redis->hGet($key, $field);;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function hDel($key, $field)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        list($data, $ttl) = $this->hBackup($key, $field);

        try
        {
            $redisEcResponse = $redisEc->hdel($key, $field);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redis->hdel($key, $field);;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_DELETE_ERROR,
                ['key' => $key]);

            $redis->hSet($key, $field, $data);

            throw $e;
        }
    }

    public function expire($key, $ttl)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->expire($key, $ttl);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redis->expire($key, $ttl);;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function incr($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            if ($this->shouldReadRedisLabs === true)
            {
                $redisLabsResponse = $redis->incr($key);

                $redisEc->set($key, $redisLabsResponse);

                return $redisLabsResponse;
            }

            return $redisEc->incr($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            $redisEc->del($key);

            $redis->decr($key);

            throw $e;
        }
    }

    public function lpop($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        // reinstantiating app so that configs updated by tests gets picked up (for test testGenerateTid)
        $app = App::getFacadeRoot();

        $config = $app['config']->get('applications.redisdualwrite');

        $this->shouldReadRedisLabs = $config['redislab_cache_read'];

        try
        {
            $redisEcResponse = $redisEc->lpop($key);

            $redisLabResponse = $redis->lpop($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    // This method is being used in GatewayProcessor/Worldline/TidGenerator.php:leftInsertIntoTidRangeList()
    // where we have to left insert into both the redis
    public function lpush($key, $value)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->lpush($key, $value);

            $redisLabResponse = $redis->lpush($key, $value);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function lrange($key, $start, $end)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $redisEcResponse = $redisEc->lrange($key, $start, $end);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redis->lrange($key, $start, $end);
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }
}
