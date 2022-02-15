<?php

namespace RZP\Models\P2p\Mandate\Patch;

use RZP\Models\P2p\Base;

/**
 * Class Entity for patch entity
 *
 * @package RZP\Models\P2p\Mandate
 */
class Entity extends Base\Entity
{

    const MANDATE_ID                = 'mandate_id';
    const DETAILS                   = 'details';
    const ACTION                    = 'action';
    const STATUS                    = 'status';
    const EXPIRE_AT                 = 'expiry_at';
    const ACTIVE                    = 'active';
    const REMARKS			        = 'remarks';

    /************** Entity Properties ************/

    protected $entity       = 'p2p_mandates_patch';

    protected $dates = [
        Entity::EXPIRE_AT
    ];

    protected $fillable = [
        Entity::MANDATE_ID,
        Entity::DETAILS,
        Entity::ACTION,
        Entity::STATUS,
        Entity::EXPIRE_AT,
        Entity::ACTIVE,
        Entity::REMARKS
    ];

    protected $visible = [
        Entity::MANDATE_ID,
        Entity::DETAILS,
        Entity::ACTION,
        Entity::STATUS,
        Entity::EXPIRE_AT,
        Entity::ACTIVE,
        Entity::REMARKS,
    ];

    protected $public = [
        Entity::MANDATE_ID,
        Entity::DETAILS,
        Entity::ACTION,
        Entity::STATUS,
        Entity::EXPIRE_AT,
        Entity::ACTIVE,
        Entity::REMARKS
    ];

    protected $defaults = [
        Entity::DETAILS                     => null,
        Entity::ACTION                      => null,
        Entity::STATUS                      => null,
        Entity::EXPIRE_AT                   => null,
        Entity::ACTIVE                      => true,
        Entity::REMARKS                     => null
    ];

    protected $casts = [
        Entity::MANDATE_ID                   => 'string',
        Entity::DETAILS                      => 'array',
        Entity::ACTION                       => 'string',
        Entity::STATUS                       => 'string',
        Entity::EXPIRE_AT                    => 'int',
        Entity::ACTIVE                       => 'boolean',
        Entity::REMARKS                      => 'string'
    ];
}
