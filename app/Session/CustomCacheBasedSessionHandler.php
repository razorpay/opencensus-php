<?php

namespace App\Session;


use Auth;
use Config;
use Illuminate\Contracts\Auth\Guard;
use App\Utils\RegionUtils\RegionUtils;
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

    /**
     * Read session data based on storage type determined by cross-region requirements
     */
    public function read($sessionId): string
    {
        $storageType = RegionUtils::getSessionStorageType();

        if ($storageType === SessionConstants::STORAGE_TYPE_MEMORY_DB) {
            return $this->readFromMemoryDb($sessionId);
        } else {
            return $this->readFromRedis($sessionId);
        }
    }

    /**
     * Read session data from Memory DB
     */
    private function readFromMemoryDb($sessionId): string
    {
        $data = SessionUtils::getSessionDataFromMemoryDb($sessionId);

        if (SessionUtils::isSessionExpired($data))
        {
            $this->destroyFromMemoryDb($sessionId);

            return '';
        }

        if (isset($data['payload'])) {
            return $data['payload'];
        }

        return '';
    }

    /**
     * Read session data from Redis
     */
    private function readFromRedis($sessionId): string
    {
        $connection = $this->cache->connection()->client();
        $key = SessionUtils::getSessionKey($sessionId);
        $data = $connection->hgetall($key);

        if (isset($data['payload'])) {
            return $data['payload'];
        }

        return '';
    }

    /**
     * Write session data based on storage type and handle migration if needed
     */
    public function write($sessionId, $data): bool
    {
        // Don't override session if it is already handled
        // Currently sessions are manually modified by edge team
        // to keep dashboard sessions in sync with edge on ValidateEdgeToken.php
        // Laravel session handler will try to override the manual modifications
        // hence we use this flag to identify if the session has to be overridden or not
        $shouldOverrideSession = app('request.ctx')->shouldOverrideSession();
        if (!$shouldOverrideSession) {
            return true;
        }

        $data = $this->getDefaultPayload($data, app());

        $lifetime = $this->getLifetime($data);

        $data[SessionUtils::SESSION_TIMEOUT_KEY] = SessionUtils::getSessionTimeout($lifetime);

        // Get session storage storageConfig from RegionUtils
        $storageConfig = RegionUtils::getSessionStorageConfiguration();
        $storageType = $storageConfig[SessionConstants::STRATEGY_KEY_STORAGE_TYPE];
        $requiresMigration = $storageConfig[SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION];

        // Handle migration if required (from Redis to Memory DB)
        if ($requiresMigration) {
            $this->migrateSessionFromRedisToMemoryDb($sessionId, $data);
        }

        // Write to appropriate storage based on storage type
        if ($storageType === SessionConstants::STORAGE_TYPE_MEMORY_DB) {
            return $this->writeToMemoryDb($sessionId, $data);
        } else {
            $connection = $this->cache->connection()->client();
            return $this->writeToRedis($connection, $sessionId, $data, $lifetime);
        }
    }

    /**
     * Migrate session from Redis to Memory DB by cleaning up Redis storage
     */
    private function migrateSessionFromRedisToMemoryDb($sessionId, $data): void
    {
        $connection = $this->cache->connection()->client();
        $this->destroyFromRedis($connection, $sessionId, $data);
    }

    /**
     * Write session data to Memory DB
     */
    private function writeToMemoryDb($sessionId, $data): bool
    {
        SessionUtils::writeSessionToMemoryDb($sessionId, $data);
        return true;
    }

    /**
     * Write session data to Redis with observability (existing functionality)
     */
    protected function writeToRedis($connection, $sessionId, $data, $lifetime): bool
    {

        // Write to admins:adminID:sessions = [ Sid1, Sid2, Sid3 ]
        if (isset($data['admin_id']))
        {
            $adminKey = SessionUtils::getAdminSessionKey($data['admin_id']);

            $connection->sadd($adminKey, $sessionId);

            $connection->expire($adminKey, $lifetime);
        }

        // Write to users:userID:sessions = [ Sid1, Sid2, Sid3 ]
        if (isset($data['user_id']))
        {
            $userKey = SessionUtils::getUserSessionKey($data['user_id']);

            $connection->sadd($userKey, $sessionId);

            $connection->expire($userKey, $lifetime);
        }

        $sessionKey = SessionUtils::getSessionKey($sessionId);

        // Write to the main cache (hash)

        $connection->hmset($sessionKey, $data);

        $connection->expire($sessionKey, $lifetime);

        return true;
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

    /**
     * Destroy session data based on storage type
     */
    public function destroy($sessionId): bool
    {
        $storageType = RegionUtils::getSessionStorageType();

        if ($storageType === SessionConstants::STORAGE_TYPE_MEMORY_DB) {
            return $this->destroyFromMemoryDb($sessionId);
        } else {
            $connection = $this->cache->connection()->client();
            $key = SessionUtils::getSessionKey($sessionId);
            $data = $connection->hgetall($key);
            return $this->destroyFromRedis($connection, $sessionId, $data);
        }
    }

    /**
     * Destroy session from Memory DB
     */
    private function destroyFromMemoryDb($sessionId): bool
    {
        // Get session data first for cleanup
        $data = SessionUtils::getSessionDataFromMemoryDb($sessionId);

        // Cleanup session from Memory DB
        SessionUtils::cleanupSessionFromMemoryDb($sessionId, $data, SessionConstants::CONTEXT_SESSION_HANDLER);

        return true;
    }

    /**
     * Destroy session from Redis (existing functionality)
     */
    protected function destroyFromRedis($connection, $sessionId, $data): bool
    {
        $key = SessionUtils::getSessionKey($sessionId);

        // Delete the key holding entire session data
        $connection->del($key);

        // Get rid of the relation from the users set
        if (empty($data['user_id']) === false)
        {
            $userId = $data['user_id'];

            $userKey = SessionUtils::getUserSessionKey($userId);

            $connection->srem($userKey, $sessionId);
        }

        // Get rid of the relation from the admins set
        if (empty($data['admin_id']) === false)
        {
            $adminId = $data['admin_id'];

            $adminKey = SessionUtils::getAdminSessionKey($adminId);

            $connection->srem($adminKey, $sessionId);
        }

        return true;
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
