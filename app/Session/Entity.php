<?php

namespace App\Session;

use App\Base;
use App\Edge\EdgeClient;
use Http\Client\Common\Exception\ServerErrorException;

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

    /**
     * @var \GuzzleHttp\Client|null
     */
    protected $edgeClient;

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
        $setKey = $this->getUserSessionKey($userId);

        $sessionIds = Redis::smembers($setKey);

        $this->edgeClient = new EdgeClient();
        // revoke all tokens on edge for the current user
        try {
            $exclude_current_session = (! empty($currentSessionId));
            $this->edgeClient->revokeToken($userId, $exclude_current_session);
        } catch (\Exception $e) {
            // return if user token can not be revoked at edge
            // we will not alter the sessions at redis unless edge tokens are revoked successfully
            throw new ServerErrorException(
                "Session deletion Failed Failed to revoke user session token from edge " . $e->getMessage(),
                \Razorpay\Api\Errors\ErrorCode::SERVER_ERROR,
                500);
        }

        foreach ($sessionIds as $sessionId)
        {
            if ((empty($currentSessionId) === false) and ($sessionId === $currentSessionId))
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

            $this->deleteUserSessionRelation($userId, $sessionId);
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
        $key = $this->getSessionKey($sessionId);
        $hash = Redis::hgetall($key);

        // remove it from sessions
        Redis::del($key);
        $this->deleteUserSessionRelation($userId, $sessionId);

        // Delete from admins:adminId:sessions set as well
        if (isset($hash['admin_id']))
        {
            $this->deleteAdminSessionRelation($hash['admin_id'], $sessionId);
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
