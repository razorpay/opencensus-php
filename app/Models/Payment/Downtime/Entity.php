<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    const ID         = 'id';

    const STATUS     = 'status';
    const SCHEDULED  = 'scheduled';
    const METHOD     = 'method';
    const BEGIN      = 'begin';
    const END        = 'end';
    const SEVERITY   = 'severity';
    const ISSUER     = 'issuer';
    const TYPE       = 'type';
    const NETWORK    = 'network';
    const AUTH_TYPE  = 'auth_type';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const ONGOING    = 'ongoing';

    // the following 3 are for network, issuer and card_type
    // for the appropriate default values instead of null
    const NA         = 'NA';
    const UNKNOWN    = 'UNKNOWN';
    const ALL        = 'ALL';

    const INSTRUMENT = 'instrument';

    protected $fillable = [
        self::BEGIN,
        self::END,
        self::METHOD,
        self::STATUS,
        self::SCHEDULED,
        self::SEVERITY,
        self::ISSUER,
        self::TYPE,
        self::NETWORK,
        self::AUTH_TYPE,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::METHOD,
        self::BEGIN,
        self::END,
        self::STATUS,
        self::SCHEDULED,
        self::SEVERITY,
        self::ISSUER,
        self::TYPE,
        self::NETWORK,
        self::AUTH_TYPE,
        self::INSTRUMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::METHOD,
        self::BEGIN,
        self::END,
        self::STATUS,
        self::SCHEDULED,
        self::SEVERITY,
        // self::ISSUER,
        // self::TYPE,
        // self::NETWORK,
        // self::AUTH_TYPE,
        self::INSTRUMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::BEGIN     => 'int',
        self::END       => 'int',
        self::SCHEDULED => 'bool',
    ];

    protected $dates = [
        self::BEGIN,
        self::END,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::INSTRUMENT,
    ];

    protected $defaults = [
        self::END => null,
    ];

    protected static $sign = 'down';

    protected $entity = EntityConstants::PAYMENT_DOWNTIME;

    protected $generateIdOnCreate = true;

    public function setPublicInstrumentAttribute(array & $array)
    {
        $array[self::INSTRUMENT] = [];
    }
}
