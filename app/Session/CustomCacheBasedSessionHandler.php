<?php

namespace App\Session;

use Auth;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Auth\Guard;

class CustomCacheBasedSessionHandler extends \Illuminate\Session\CacheBasedSessionHandler
{

    protected $sessionNamespace = 'sessions';

    public function read($sessionId)
    {
        $connection = $this->cache->connection();

        $key = $this->cache->getStore()->getPrefix().$sessionId;

        $data = $connection->hgetall($key);

        if (isset($data['payload']))
        {
            $payload = $data['payload'];

            return $payload;
        }

        // return $this->cache->get($sessionId, '');
    }

    public function write($sessionId, $data)
    {
        $connection = $this->cache->connection();

        $responses = $connection->transaction(function ($tx) use ($sessionId, $data)
        {
            $lifetime = $this->minutes * 60;

            $data = $this->getDefaultPayload($data, app());

            $sessionKey = $this->cache->getStore()->getPrefix().$sessionId;

            // Write to the main cache (hash)

            $tx->hmset($sessionKey, $data);

            $tx->expire($sessionKey, $lifetime);

            // Write to admins:ID:sessions
            if (isset($data['admin_id']))
            {
                $adminKey = "admins:{$data['admin_id']}:{$this->sessionNamespace}";

                $tx->sadd($adminKey, $sessionId);

                $tx->expire($adminKey, $lifetime);
            }

            // Write to users:ID:sessions
            if (isset($data['user_id']))
            {
                $userKey = "users:{$data['user_id']}:{$this->sessionNamespace}";

                $tx->sadd($userKey, $sessionId);

                $tx->expire($userKey, $lifetime);
            }
        });

        return $responses;

        // return $this->cache->put($sessionId, $data, $this->minutes);
    }

    protected function getDefaultPayload($data, $container = null)
    {
        $payload = ['payload' => $data, 'last_activity' => time()];

        if (empty($container)) {
            return $payload;
        }

        if ($container->bound(Guard::class)) {
            if (! empty($container->make(Guard::class)->id()))
            {
                $payload['user_id'] = $container->make(Guard::class)->id();
            }
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
