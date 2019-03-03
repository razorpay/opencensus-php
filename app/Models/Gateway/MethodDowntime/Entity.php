<?php

namespace RZP\Models\Gateway\MethodDowntime;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const METHOD        = 'method';
    const BEGIN         = 'begin';
    const END           = 'end';
    const SEVERITY      = 'severity';
    const ONGOING       = 'ongoing';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    const ISSUER        = 'issuer';
    const CARD_TYPE     = 'card_type';
    const NETWORK       = 'network';

    // the following 3 are for network, issuer and card_type
    // for the appropriate default values instead of null
    const NA            = 'NA';
    const UNKNOWN       = 'UNKNOWN';
    const ALL           = 'ALL';

    const INSTRUMENT    = 'instrument';

    protected $fillable = [
        self::BEGIN,
        self::END,
        self::METHOD,
    ];

    protected $visible = [
        self::ID,
        self::METHOD,
        self::BEGIN,
        self::END,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
    ];

    protected $casts = [
        self::BEGIN     => 'int',
        self::END       => 'int',
    ];

    protected $dates = [
        self::BEGIN,
        self::END,
    ];

    protected $defaults = [
        self::END           => null,
    ];

    protected $entity = EntityConstants::METHOD_DOWNTIME;

    protected $generateIdOnCreate = true;
}
