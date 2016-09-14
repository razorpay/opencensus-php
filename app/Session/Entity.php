<?php

namespace App\Session;

use App\Base;

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
        return $this->where(self::ADMIN_ID, $id)->get(self::$public);
    }

    public function deleteAllOtherSessionsForAdmin($id, $currentSessionId)
    {
        $this->where(self::ADMIN_ID, $id)
             ->where(self::ID, '!=', $currentSessionId)->delete();
    }

    public function deleteOneSessionForAdmin($sessionId)
    {
        $this->where('id', $sessionId)->delete();
    }
}
