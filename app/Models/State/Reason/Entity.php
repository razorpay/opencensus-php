<?php

namespace RZP\Models\State\Reason;

use RZP\Models\Base;
use RZP\Models\State;

class Entity extends Base\PublicEntity
{
    const STATE_ID        = 'state_id';
    const REASON_TYPE     = 'reason_type';
    const REASON_CATEGORY = 'reason_category';
    const REASON_CODE     = 'reason_code';
    const CREATED_AT      = 'created_at';
    const UPDATED_AT      = 'updated_at';

    protected $entity = 'state_reason';

    protected $fillable = [
        self::STATE_ID,
        self::REASON_TYPE,
        self::REASON_CATEGORY,
        self::REASON_CODE,
    ];

    protected $visible = [
        self::ID,
        self::STATE_ID,
        self::REASON_TYPE,
        self::REASON_CATEGORY,
        self::REASON_CODE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::STATE_ID,
        self::REASON_TYPE,
        self::REASON_CATEGORY,
        self::REASON_CODE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function state()
    {
        return $this->belongsTo(State\Entity::class);
    }
}
