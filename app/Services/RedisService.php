<?php

namespace RZP\Services;

use Illuminate\Support\Facades\Redis;
use Predis\PredisException;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class RedisService
{
    protected $trace;

    /**
     * @var \Illuminate\Redis\Connections\Connection
     */
    private $redis;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->redis = Redis::connection();

    }

    public function set($key, $value, $expireTTL, $expireResolution=false,$flag=false)
    {
        try
        {
            return $this->redis->set($key, $value, $expireResolution, $expireTTL, $flag);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_STORE_ERROR,
                ['key' => $key]);

            $this->redis->del($key);

            throw $e;
        }
    }

    public function get($key)
    {
        try
        {
            return $this->redis->get($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function ttl($key)
    {


        try
        {
            return $this->redis->ttl($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }

    }


    public function del($key)
    {

        list($data, $ttl) = $this->backup($key);

        try
        {
            return $this->redis->del($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_DELETE_ERROR,
                ['key' => $key]);

            $this->restore($key, $data, $ttl);

            throw $e;
        }
    }

    public function hGetAll($key)
    {


        try
        {
            return $this->redis->hGetAll($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    protected function backup($key)
    {
        $data = null;
        $ttl  = null;



        try
        {
            $data = $this->redis->get($key);
            $ttl  = $this->redis->ttl($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_BACKUP_ERROR,
                ['key' => $key]);

        }

        return [$data, $ttl];
    }

    protected function hBackup($key, $field)
    {
        $data = null;
        $ttl  = null;



        try
        {
            $data = $this->redis->hGet($key, $field);
            $ttl  = $this->redis->ttl($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_BACKUP_ERROR,
                ['key' => $key]);

        }

        return [$data, $ttl];
    }

    protected function restore($key, $data, $ttl)
    {
        $this->set($key, $data, 'ex', $ttl, 'nx');
    }

    public function hSet($key, $field, $value)
    {


        try
        {
            return $this->redis->hSet($key, $field, $value);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            $this->redis->hdel($key, $value);

            throw $e;
        }
    }

    public function hGet($key, $field)
    {


        try
        {
            return $this->redis->hGet($key, $field);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function hDel($key, $field)
    {


        list($data, $ttl) = $this->hBackup($key, $field);

        try
        {
            return $this->redis->hdel($key, $field);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_DELETE_ERROR,
                ['key' => $key]);

            $this->redis->hSet($key, $field, $data);

            throw $e;
        }
    }

    public function expire($key, $ttl)
    {


        try
        {
            return $this->redis->expire($key, $ttl);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function incr($key)
    {


        try
        {
            return $this->redis->incr($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            $this->redis->decr($key);

            throw $e;
        }
    }

    public function lpop($key)
    {


        try
        {
            return $this->redis->lpop($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function lpush($key, $value)
    {


        try
        {
            return $this->redis->lpush($key, $value);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }

    public function lrange($key, $start, $end)
    {


        try
        {
            return $this->redis->lrange($key, $start, $end);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }
    }
}
