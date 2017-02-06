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

        $sessionIds = Redis::smembers("admins:$id:sessions");

        foreach ($sessionIds as $sessionId)
        {
            $hash = Redis::hgetall("dashboard_:$sessionId");

            $hash['id'] = $sessionId;

            $sessions[] = $hash;
        }

        return $sessions;

        // return $this->where(self::ADMIN_ID, $id)->get(self::$public);
    }

    public function deleteAllOtherSessionsForAdmin($id, $currentSessionId)
    {
        $this->where(self::ADMIN_ID, $id)
             ->where(self::ID, '!=', $currentSessionId)->delete();
    }

    public function deleteAllOtherSessionsForUser($userId, $currentSessionId)
    {
        $this->where(self::USER_ID, $userId)
             ->where(self::ID, '!=', $currentSessionId)->delete();
    }

    public function deleteOneSessionForAdmin($sessionId)
    {
        $hash = Redis::hgetall("dashboard_:$sessionId");

        // Remove from admins:adminId:sessions
        if ($hash['admin_id'])
        {
            $key = "admins:".$hash['admin_id'].":sessions";

            Redis::srem($key, $sessionId);
        }

        Redis::del("dashboard_:$sessionId");

        // $this->where(self::ID, $sessionId)->delete();
    }

    public function deleteAllSessionsForAdmin($adminId)
    {
        // Get all the members of set
        $setKey = "admins:".$adminId.":sessions";

        $sessionIds = Redis::smembers($setKey);

        foreach ($sessionIds as $sessionId)
        {
            // Delete individual session entities
            $key = "dashboard_:$sessionId";

            Redis::del($key);
        }

        Redis::del($setKey);

        // $this->where(self::ADMIN_ID, $adminId)->delete();
    }
}
