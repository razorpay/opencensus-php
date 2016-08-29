<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const GATEWAY                       = 'gateway';
    const BANK                          = 'bank';
    const FROM                          = 'from';
    const TO                            = 'to';
    const REASON                        = 'reason';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';


    protected $fillable = array(
        self::GATEWAY,
        self::FROM,
        self::TO,
        self::REASON
    );

    protected $public = array(
        self::ID,
        self::GATEWAY,
        self::FROM,
        self::TO,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REASON
    );

    const END_OF_TIME = 2147483647;

    protected $table = \RZP\Constants\Table::GATEWAYSTATUS_ABSENCE;

    protected $entity = 'gateway_absence';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $generateIdOnCreate = true;

    public function getGateway()
    {
        return $this->getAttributes(self::GATEWAY);
    }

    public function getDowntimeFrom()
    {
        return $this->getAttributes(self::FROM);
    }

    public function getDowntimeTo()
    {
        return $this->getAttributes(self::TO);
    }

    public function getReason()
    {
        return $this->getAttributes(self::REASON);
    }

    public function getBank()
    {
        return $this->getAttributes(self::BANK);
    }

}
