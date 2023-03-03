<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const NETWORK             = 'network';
    const GATEWAY_CODE        = 'gateway_code';
    const GATEWAY_DESCRIPTION = 'gateway_description';
    const CODE                = 'code';
    const DESCRIPTION         = 'description';
    const CREATED_AT          = 'created_at';
    const UPDATED_AT          = 'updated_at';

    protected $entity = 'dispute_reason';

    // Bulk Crete Disputes related constants
    // network-gateway_code  Ex:- Visa-85
    const NETWORK_CODE = 'network_code';
    const REASON_CODE  = 'reason_code';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NETWORK,
        self::GATEWAY_CODE,
        self::GATEWAY_DESCRIPTION,
        self::CODE,
        self::DESCRIPTION,
    ];

    protected $visible = [
        self::ID,
        self::NETWORK,
        self::GATEWAY_CODE,
        self::GATEWAY_DESCRIPTION,
        self::CODE,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::GATEWAY_CODE,
        self::GATEWAY_DESCRIPTION,
        self::CODE,
        self::DESCRIPTION,
        self::NETWORK,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $guarded = [self::ID];

    public function getCode()
    {
        return $this->getAttribute(self::CODE);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function toDualWriteArray() : array
    {
        return $this->toArray();
    }
}
