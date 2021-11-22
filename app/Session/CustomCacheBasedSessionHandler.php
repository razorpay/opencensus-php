<?php

namespace App\Session;

use App;
use Auth;
use Config;
use App\Trace\TraceCode;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class CustomCacheBasedSessionHandler extends \Illuminate\Session\CacheBasedSessionHandler
{
    protected $sessionNamespace = 'sessions';

    protected $nonLoggedInUserSessionTimeout;

    public function __construct(CacheContract $cache, $loggedInUserSessionTimeout, $nonLoggedInUserSessionTimeout = 60)
    {
        parent::__construct($cache, $loggedInUserSessionTimeout);

        $this->nonLoggedInUserSessionTimeout = $nonLoggedInUserSessionTimeout;
    }


    public function read($sessionId)
    {
        $connection = $this->cache->connection();

        $key = $this->sessionNamespace.':'.$sessionId;

        $data = $connection->hgetall($key);

        if (isset($data['payload']))
        {
            $payload = $data['payload'];

            return $payload;
        }
    }

    public function write($sessionId, $data)
    {
        $connection = $this->cache->connection();

        $responses = $connection->transaction(function ($tx) use ($sessionId, $data)
        {
            $data = $this->getDefaultPayload($data, app());

            $lifetime = $this->getLifetime($data);

            $sessionKey = $this->sessionNamespace.':'.$sessionId;

            // Write to the main cache (hash)

            $tx->hmset($sessionKey, $data);

            $tx->expire($sessionKey, $lifetime);

            // Write to admins:adminID:sessions = [ Sid1, Sid2, Sid3 ]
            if (isset($data['admin_id']))
            {
                $adminKey = $this->getAdminSessionKey($data['admin_id']);

                $tx->sadd($adminKey, $sessionId);

                $tx->expire($adminKey, $lifetime);
            }

            // Write to users:userID:sessions = [ Sid1, Sid2, Sid3 ]
            if (isset($data['user_id']))
            {
                $userKey = $this->getUserSessionKey($data['user_id']);

                $tx->sadd($userKey, $sessionId);

                $tx->expire($userKey, $lifetime);
            }
        });

        return $responses;
    }

    protected function getDefaultPayload($data, $container = null)
    {
        $payload = [
            'payload' => $data
        ];

        if (empty($container))
        {
            return $payload;
        }

        if ($container->bound(Guard::class))
        {
            if (! empty($container->make(Guard::class)->id()))
            {
                $payload['user_id'] = $container->make(Guard::class)->id();
            }
        }

        if ($container->bound('request'))
        {
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

    public function destroy($sessionId)
    {
        $connection = $this->cache->connection();

        $key = $this->getSessionKey($sessionId);

        $data = $connection->hgetall($key);

        $responses = $connection->transaction(function ($tx) use ($sessionId, $data, $key)
        {
            // Get rid of the relation from the users set
            if (empty($data['user_id']) === false)
            {
                $userId = $data['user_id'];

                $userKey = $this->getUserSessionKey($userId);

                $tx->srem($userKey, $sessionId);
            }

            // Get rid of the relation from the admins set
            if (empty($data['admin_id']) === false)
            {
                $adminId = $data['admin_id'];

                $adminKey = $this->getAdminSessionKey($adminId);

                $tx->srem($adminKey, $sessionId);
            }

            // Delete the key holding entire session data
            $tx->del($key);
        });

        return $responses;
    }

    private function getSessionKey($sessionId)
    {
        return $this->sessionNamespace . ":$sessionId";
    }

    private function getAdminSessionKey($adminId)
    {
        return "admins:$adminId:" . $this->sessionNamespace;
    }

    private function getUserSessionKey($userId)
    {
        return "users:$userId:" . $this->sessionNamespace;
    }


    protected function getLifetime($data)
    {
        if ($this->isUserLoggedIn($data) === false)
        {
            return $this->nonLoggedInUserSessionTimeout * 60;
        }

        return $this->minutes * 60;
    }

    protected function isUserLoggedIn($data)
    {
        if ((isset($data['user_id']) === false) and
            (isset($data['admin_id']) === false))
        {
            return false;
        }
        return true;
    }
}
