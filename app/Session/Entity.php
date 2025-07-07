<?php

namespace App\Session;

use App\Base;
use Http\Client\Common\Exception\ServerErrorException;
use App\Trace\TraceCode;
use App\Session\SessionConstants;

use Redis;
use Session;

class Entity extends Base\Entity
{
    const ID            = 'id';
    const PAYLOAD       = 'payload';
    const IP_ADDRESS    = 'ip_address';
    const USER_AGENT    = 'user_agent';
    const LAST_ACTIVITY = 'last_activity';
    const USER_ID       = 'user_id';
    const ADMIN_ID      = 'admin_id';

    protected $table = 'sessions';

    public $incrementing = false;

    protected $fillable = array(
        self::PAYLOAD,
        self::IP_ADDRESS,
        self::USER_AGENT,
        self::LAST_ACTIVITY,
        self::USER_ID,
        self::ADMIN_ID
    );

    protected static $public = array(
        self::ID,
        self::IP_ADDRESS,
        self::USER_AGENT,
        self::LAST_ACTIVITY,
        self::ADMIN_ID);

    public function getAllSessionsForAdmin($id)
    {
        // Get sessions from Redis
        $redisSessions = $this->getAllSessionsForAdminFromRedis($id);

        // Get sessions from Memory DB
        $memoryDbSessions = $this->getAllSessionsForAdminFromMemoryDb($id);

        // Merge and deduplicate sessions by session ID
        $allSessions = $this->mergeAndDeduplicateSessions($redisSessions, $memoryDbSessions);

        return $allSessions;
    }

    /**
     * Get all sessions for admin from Redis only
     */
    private function getAllSessionsForAdminFromRedis($id): array
    {
        $sessions = [];
        $setKey = SessionUtils::getAdminSessionKey($id);

        // Read admin sessions from Redis
        $sessionIds = Redis::smembers($setKey);

        foreach ($sessionIds as $sessionId)
        {
            $key = SessionUtils::getSessionKey($sessionId);

            // Read session data from Redis
            $hash = Redis::hgetall($key);

            // This is a very edge-case scenario bug fix
            //
            // If for some reason the main session hash doesn't exist
            // or the key is lost for some reason (bug in code, memory issue, etc.)
            // we should check for the relations and nuke them as well.
            //
            // Ofcourse since we don't have the userId we can't do anything
            // about the user relation.
            if (empty($hash))
            {
                $this->deleteAdminSessionRelationFromRedis($id, $sessionId);
                continue;
            }

            $hash['id'] = $sessionId;
            $hash['source'] = 'redis'; // Track source for debugging

            $sessions[] = $hash;
        }

        return $sessions;
    }

    /**
     * Get all sessions for admin from Memory DB only
     */
    private function getAllSessionsForAdminFromMemoryDb($id): array
    {
        $sessions = [];
        $setKey = SessionUtils::getAdminSessionKey($id);

        // Read admin sessions from Memory DB
        $sessionIds = SessionUtils::getSessionIdsFromMemoryDb($setKey);

        foreach ($sessionIds as $sessionId)
        {
            // Read session data from Memory DB
            $hash = SessionUtils::getSessionDataFromMemoryDb($sessionId);

            // Handle edge case where session data doesn't exist
            if (empty($hash))
            {
                $this->deleteAdminSessionRelationFromMemoryDb($id, $sessionId);
                continue;
            }

            $hash['id'] = $sessionId;
            $hash['source'] = 'memory_db'; // Track source for debugging

            $sessions[] = $hash;
        }

        return $sessions;
    }

    /**
     * Merge and deduplicate sessions from Redis and Memory DB
     * Priority: Memory DB data takes precedence over Redis data for same session ID
     */
    private function mergeAndDeduplicateSessions(array $redisSessions, array $memoryDbSessions): array
    {
        $sessionMap = [];

        // First, add all Redis sessions
        foreach ($redisSessions as $session) {
            $sessionId = $session['id'];
            $sessionMap[$sessionId] = $session;
        }

        // Then, add Memory DB sessions (overwriting Redis data for same session ID)
        foreach ($memoryDbSessions as $session) {
            $sessionId = $session['id'];
            // Memory DB data takes precedence
            $sessionMap[$sessionId] = $session;
        }

        // Convert back to indexed array
        return array_values($sessionMap);
    }

    public function deleteAllOtherSessionsForAdmin($id, $currentSessionId)
    {
        // Delete sessions from Redis
        $this->deleteAllOtherSessionsForAdminFromRedis($id, $currentSessionId);

        // Delete sessions from Memory DB
        $this->deleteAllOtherSessionsForAdminFromMemoryDb($id, $currentSessionId);
    }

    /**
     * Delete all other admin sessions from Redis only
     */
    private function deleteAllOtherSessionsForAdminFromRedis($id, $currentSessionId)
    {
        $setKey = SessionUtils::getAdminSessionKey($id);

        // Read admin sessions from Redis
        $sessionIds = Redis::smembers($setKey);

        foreach ($sessionIds as $sessionId)
        {
            if ($sessionId === $currentSessionId)
            {
                continue;
            }

            $key = SessionUtils::getSessionKey($sessionId);

            // Read session data from Redis
            $hash = Redis::hgetall($key);

            // This is a very edge-case scenario bug fix
            //
            // If for some reason the main session hash doesn't exist
            // or the key is lost for some reason (bug in code, memory issue, etc.)
            // we should check for the relations and nuke them as well.
            //
            // Ofcourse since we don't have the userId we can't do anything
            // about the user relation.
            if (empty($hash))
            {
                $this->deleteAdminSessionRelationFromRedis($id, $sessionId);
                continue;
            }

            // Delete session from Redis only
            $this->deleteSessionFromRedis($sessionId, $hash);
        }
    }

    /**
     * Delete all other admin sessions from Memory DB only
     */
    private function deleteAllOtherSessionsForAdminFromMemoryDb($id, $currentSessionId)
    {
        $setKey = SessionUtils::getAdminSessionKey($id);

        // Read admin sessions from Memory DB
        $sessionIds = SessionUtils::getSessionIdsFromMemoryDb($setKey);

        foreach ($sessionIds as $sessionId)
        {
            if ($sessionId === $currentSessionId)
            {
                continue;
            }

            // Read session data from Memory DB
            $hash = SessionUtils::getSessionDataFromMemoryDb($sessionId);

            // Handle edge case where session data doesn't exist
            if (empty($hash))
            {
                $this->deleteAdminSessionRelationFromMemoryDb($id, $sessionId);
                continue;
            }

            // Delete session from Memory DB only
            $this->deleteSessionFromMemoryDb($sessionId, $hash);
        }
    }

    /**
     * The currentSessionId is optional so if it is not passed
     * all sessions will be deleted. One use of this case is when
     * the user is removed from a merchant's team and we delete
     * all his active sessions.
     *
     * @param        $userId
     * @param string $currentSessionId
     */
    public function deleteSessionsForUser($userId, $currentSessionId = null)
    {
        // Delete sessions from Redis
        $this->deleteSessionsForUserFromRedis($userId, $currentSessionId);

        // Delete sessions from Memory DB
        $this->deleteSessionsForUserFromMemoryDb($userId, $currentSessionId);
    }

    /**
     * Delete user sessions from Redis only
     */
    private function deleteSessionsForUserFromRedis($userId, $currentSessionId = null)
    {
        $setKey = SessionUtils::getUserSessionKey($userId);

        // Read user sessions from Redis
        $sessionIds = Redis::smembers($setKey);

        foreach ($sessionIds as $sessionId)
        {
            if ((empty($currentSessionId) === false) and ($sessionId === $currentSessionId))
            {
                continue;
            }

            $key = SessionUtils::getSessionKey($sessionId);

            // Read session data from Redis
            $hash = Redis::hgetall($key);

            // Delete session from Redis only
            $this->deleteSessionFromRedis($sessionId, $hash);
        }
    }

    /**
     * Delete user sessions from Memory DB only
     */
    private function deleteSessionsForUserFromMemoryDb($userId, $currentSessionId = null)
    {
        $setKey = SessionUtils::getUserSessionKey($userId);

        // Read user sessions from Memory DB
        $sessionIds = SessionUtils::getSessionIdsFromMemoryDb($setKey);

        foreach ($sessionIds as $sessionId)
        {
            if ((empty($currentSessionId) === false) and ($sessionId === $currentSessionId))
            {
                continue;
            }

            // Read session data from Memory DB
            $hash = SessionUtils::getSessionDataFromMemoryDb($sessionId);

            // Delete session from Memory DB only
            $this->deleteSessionFromMemoryDb($sessionId, $hash);
        }
    }

    /**
     * Deletes current session for the user, used by Edge team to revoke user sessions
     * on Edge authentication result
     *
     * @param        $userId
     */
    public function deleteCurrentSessionForUser($userId)
    {
        $sessionId = Session::getId();

        // Delete from Redis if exists
        $this->deleteCurrentSessionForUserFromRedis($userId, $sessionId);

        // Delete from Memory DB if exists
        $this->deleteCurrentSessionForUserFromMemoryDb($userId, $sessionId);
    }

    /**
     * Delete current session for user from Redis only
     */
    private function deleteCurrentSessionForUserFromRedis($userId, $sessionId)
    {
        $key = SessionUtils::getSessionKey($sessionId);

        // Read session data from Redis
        $hash = Redis::hgetall($key);

        // Only delete if session exists in Redis
        if (!empty($hash))
        {
            // Delete session from Redis only
            $this->deleteSessionFromRedis($sessionId, $hash);
        }
    }

    /**
     * Delete current session for user from Memory DB only
     */
    private function deleteCurrentSessionForUserFromMemoryDb($userId, $sessionId)
    {
        // Read session data from Memory DB
        $hash = SessionUtils::getSessionDataFromMemoryDb($sessionId);

        // Only delete if session exists in Memory DB
        if (!empty($hash))
        {
            // Delete session from Memory DB only
            $this->deleteSessionFromMemoryDb($sessionId, $hash);
        }
    }

    public function deleteOneSessionForAdmin($sessionId)
    {
        // Delete from Redis if exists
        $this->deleteOneSessionForAdminFromRedis($sessionId);

        // Delete from Memory DB if exists
        $this->deleteOneSessionForAdminFromMemoryDb($sessionId);
    }

    /**
     * Delete one specific session for admin from Redis only
     */
    private function deleteOneSessionForAdminFromRedis($sessionId)
    {
        $key = SessionUtils::getSessionKey($sessionId);

        // Read session data from Redis
        $hash = Redis::hgetall($key);

        // Only delete if session exists in Redis
        if (!empty($hash))
        {
            // Delete session from Redis only
            $this->deleteSessionFromRedis($sessionId, $hash);
        }
    }

    /**
     * Delete one specific session for admin from Memory DB only
     */
    private function deleteOneSessionForAdminFromMemoryDb($sessionId)
    {
        // Read session data from Memory DB
        $hash = SessionUtils::getSessionDataFromMemoryDb($sessionId);

        // Only delete if session exists in Memory DB
        if (!empty($hash))
        {
            // Delete session from Memory DB only
            $this->deleteSessionFromMemoryDb($sessionId, $hash);
        }
    }

    /*
        Delete a session ID from admins:adminId:sessions
    */
    private function deleteAdminSessionRelation($adminId, $sessionId)
    {
        // Use Redis-only deletion since we now handle each storage separately
        return $this->deleteAdminSessionRelationFromRedis($adminId, $sessionId);
    }

    /*
        Delete a session ID from users:userId:sessions
    */
    private function deleteUserSessionRelation($userId, $sessionId)
    {
        // Use Redis-only deletion since we now handle each storage separately
        return $this->deleteUserSessionRelationFromRedis($userId, $sessionId);
    }

    /**
     * Delete a session from Redis only with all related cleanup
     */
    private function deleteSessionFromRedis($sessionId, $hash)
    {
        $key = SessionUtils::getSessionKey($sessionId);

        // Delete the main session data from Redis
        Redis::del($key);

        // Clean up admin session relations from Redis
        if (isset($hash['admin_id']))
        {
            $this->deleteAdminSessionRelationFromRedis($hash['admin_id'], $sessionId);
        }

        // Clean up user session relations from Redis
        if (isset($hash['user_id']))
        {
            $this->deleteUserSessionRelationFromRedis($hash['user_id'], $sessionId);
        }
    }

    /**
     * Delete a session from Memory DB only with all related cleanup
     */
    private function deleteSessionFromMemoryDb($sessionId, $hash)
    {
        // Delete the main session data from Memory DB
        SessionUtils::cleanupSessionFromMemoryDb($sessionId, $hash, SessionConstants::CONTEXT_SESSION_DELETE);

        // Clean up admin session relations from Memory DB
        if (isset($hash['admin_id']))
        {
            $this->deleteAdminSessionRelationFromMemoryDb($hash['admin_id'], $sessionId);
        }

        // Clean up user session relations from Memory DB
        if (isset($hash['user_id']))
        {
            $this->deleteUserSessionRelationFromMemoryDb($hash['user_id'], $sessionId);
        }
    }

    /**
     * Delete admin session relation from Redis only
     */
    private function deleteAdminSessionRelationFromRedis($adminId, $sessionId)
    {
        $key = SessionUtils::getAdminSessionKey($adminId);
        return Redis::srem($key, $sessionId);
    }

    /**
     * Delete admin session relation from Memory DB only
     */
    private function deleteAdminSessionRelationFromMemoryDb($adminId, $sessionId)
    {
        $key = SessionUtils::getAdminSessionKey($adminId);
        SessionUtils::deleteSessionRelationFromMemoryDb($key, $sessionId, null, $adminId, SessionConstants::ACTION_ADMIN_SESSION_DELETE_FROM_MEMORY_DB_SET);
    }

    /**
     * Delete user session relation from Redis only
     */
    private function deleteUserSessionRelationFromRedis($userId, $sessionId)
    {
        $key = SessionUtils::getUserSessionKey($userId);
        return Redis::srem($key, $sessionId);
    }

    /**
     * Delete user session relation from Memory DB only
     */
    private function deleteUserSessionRelationFromMemoryDb($userId, $sessionId)
    {
        $key = SessionUtils::getUserSessionKey($userId);
        SessionUtils::deleteSessionRelationFromMemoryDb($key, $sessionId, $userId, null, SessionConstants::ACTION_USER_SESSION_DELETE_FROM_MEMORY_DB_SET);
    }
}
