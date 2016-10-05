<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const GATEWAY                       = 'gateway';
    const BANK                          = 'bank';
    const FROM                          = 'from';
    const TO                            = 'to';
    const REASON                        = 'reason';
    const SCHEDULED                     = 'scheduled';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    protected $fillable = array(
        self::GATEWAY,
        self::FROM,
        self::TO,
        self::REASON,
        self::BANK,
        self::SCHEDULED
    );

    protected $public = array(
        self::ID,
        self::GATEWAY,
        self::FROM,
        self::BANK,
        self::TO,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REASON,
        self::SCHEDULED
    );

    protected $casts = [
        self::FROM      => 'int',
        self::TO        => 'to',
        self::SCHEDULED => 'bool'
    ];

    const END_OF_TIME = 2147483647;

    protected $table = Table::GATEWAY_STATUS_ABSENCE;

    protected $entity = 'gateway_absence';

    protected $generateIdOnCreate = true;

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getFrom()
    {
        return $this->getAttribute(self::FROM);
    }

    public function getTo()
    {
        return $this->getAttribute(self::TO);
    }

    public function getReason()
    {
        return $this->getAttribute(self::REASON);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

}
