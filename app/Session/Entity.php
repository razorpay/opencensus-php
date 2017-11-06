<?php

namespace App\Session;

use App\Base;
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
        $sessions = [];

        $setKey = $this->getAdminSessionKey($id);

        $sessionIds = Redis::smembers($setKey);

        foreach ($sessionIds as $sessionId)
        {
            $key = $this->getSessionKey($sessionId);

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
                $this->deleteAdminSessionRelation($id, $sessionId);

                continue;
            }

            $hash['id'] = $sessionId;

            $sessions[] = $hash;
        }

        return $sessions;
    }

    public function deleteAllOtherSessionsForAdmin($id, $currentSessionId)
    {
        $setKey = $this->getAdminSessionKey($id);

        $sessionIds = Redis::smembers($setKey);

        foreach ($sessionIds as $sessionId)
        {
            if ($sessionId === $currentSessionId)
            {
                continue;
            }

            $key = $this->getSessionKey($sessionId);

            $hash = Redis::hgetall($key);

            Redis::del($key);

            $this->deleteAdminSessionRelation($id, $sessionId);

            // Delete from admins:adminId:sessions set as well
            if (isset($hash['user_id']))
            {
                $this->deleteUserSessionRelation($hash['user_id'], $sessionId);
            }
        }
    }

    /**
     * @param        $userId
     * @param string $currentSessionId
     */
    public function deleteAllOtherSessionsForUser($userId, $currentSessionId = null)
    {
        $setKey = $this->getUserSessionKey($userId);

        $sessionIds = Redis::smembers($setKey);

        foreach ($sessionIds as $sessionId)
        {
            if (empty($currentSessionId) === false and ($sessionId === $currentSessionId))
            {
                continue;
            }

            $key = $this->getSessionKey($sessionId);

            $hash = Redis::hgetall($key);

            Redis::del($key);

            // Delete from admins:adminId:sessions set as well
            if (isset($hash['admin_id']))
            {
                $this->deleteAdminSessionRelation($hash['admin_id'], $sessionId);
            }

            $this->deleteUserSessionRelation($setKey, $sessionId);
        }
    }

    public function deleteOneSessionForAdmin($sessionId)
    {
        $key = $this->getSessionKey($sessionId);

        $hash = Redis::hgetall($key);

        Redis::del($key);

        if (isset($hash['admin_id']))
        {
            $this->deleteAdminSessionRelation($hash['admin_id'], $sessionId);
        }

        if (isset($hash['user_id']))
        {
            $this->deleteUserSessionRelation($hash['user_id'], $sessionId);
        }
    }

    /*
        Delete a session ID from admins:adminId:sessions
    */
    private function deleteAdminSessionRelation($adminId, $sessionId)
    {
        $key = $this->getAdminSessionKey($adminId);

        return Redis::srem($key, $sessionId);
    }

    /*
        Delete a session ID from users:userId:sessions
    */
    private function deleteUserSessionRelation($userId, $sessionId)
    {
        $key = $this->getUserSessionKey($userId);

        return Redis::srem($key, $sessionId);
    }

    private function getSessionKey($sessionId)
    {
        return "sessions:$sessionId";
    }

    private function getAdminSessionKey($adminId)
    {
        return "admins:$adminId:sessions";
    }

    private function getUserSessionKey($userId)
    {
        return "users:$userId:sessions";
    }
}
