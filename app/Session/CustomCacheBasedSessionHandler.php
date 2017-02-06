<?php

namespace App\Session;

use Auth;
use Illuminate\Contracts\Auth\Guard;

class CustomCacheBasedSessionHandler extends \Illuminate\Session\CacheBasedSessionHandler
{

    public function read($sessionId)
    {
        $key = $this->cache->getStore()->getPrefix().$sessionId;

        $data = $this->cache->connection()->hgetall($key);

        if (isset($data['payload']))
        {
            $payload = $data['payload'];

            return $payload;
        }

        // return $this->cache->get($sessionId, '');
    }

    public function write($sessionId, $data)
    {
        $data = $this->getDefaultPayload($data, app());

        $key = $this->cache->getStore()->getPrefix().$sessionId;

        return $this->cache->connection()->hmset($key, $data);

        // return $this->cache->put($sessionId, $data, $this->minutes);
    }

    public function getDefaultPayload($data, $container = null)
    {
        $payload = ['payload' => $data, 'last_activity' => time()];

        if (empty($container)) {
            return $payload;
        }

        if ($container->bound(Guard::class)) {
            $payload['user_id'] = $container->make(Guard::class)->id();
        }

        if ($container->bound('request')) {
            $payload['ip_address'] = $container->make('request')->ip();

            $payload['user_agent'] = substr(
                (string) $container->make('request')->header('User-Agent'), 0, 500
            );
        }

        // Customization

        if (Auth::guard('api')->user() !== null)
        {
            $payload['admin_id'] = Auth::guard('api')->user()->id;
        }

        return $payload;
    }
}
