<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const GATEWAY                       = 'gateway';
    const BANK                          = 'bank';
    const CARD_TYPE                     = 'card_type';
    const NETWORK                       = 'network';
    const METHOD                        = 'method';
    const FROM                          = 'from';
    const TO                            = 'to';
    const REASON                        = 'reason';
    const PARTIAL                       = 'partial';
    const SCHEDULED                     = 'scheduled';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    protected $fillable = [
        self::GATEWAY,
        self::FROM,
        self::TO,
        self::REASON,
        self::BANK,
        self::SCHEDULED,
        self::PARTIAL,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
    ];

    protected $public = [
        self::ID,
        self::GATEWAY,
        self::FROM,
        self::BANK,
        self::TO,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REASON,
        self::SCHEDULED,
        self::PARTIAL,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD
    ];

    protected $casts = [
        self::FROM      => 'int',
        self::TO        => 'int',
        self::SCHEDULED => 'bool',
        self::PARTIAL   => 'bool'
    ];

    const END_OF_TIME = 2147483647;

    protected $entity = 'gateway_absence';

    protected $generateIdOnCreate = true;
}
