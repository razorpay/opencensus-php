<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const GATEWAY                       = 'gateway';
    const DOWNTIME_FROM                 = 'from';
    const DOWNTIME_TO                   = 'to';
    const REASON                        = 'reason';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';


    protected $fillable = array(
        self::GATEWAY,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO,
        self::REASON
    );

    protected $public = array(
        self::ID,
        self::GATEWAY,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REASON
    );

    protected $table = \RZP\Constants\Table::GATEWAYSTATUS_ABSENCE;

    protected $entity = 'gateway_absence';

    protected static $sign = '';

    protected static $delimiter = '';

    public function getGateway()
    {
        return $this->getAttributes(self::GATEWAY);
    }

    public function getDowntimeFrom()
    {
        return $this->getAttributes(self::DOWNTIME_FROM);
    }

    public function getDowntimeTo()
    {
        return $this->getAttributes(self::DOWNTIME_TO);
    }

    public function getReason()
    {
        return $this->getAttributes(self::REASON);
    }

}
