<?php

namespace RZP\Models\Merchant\Slab;

use RZP\Models\Base;
use RZP\Models\Merchant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID          = 'id';
    const SLAB        = 'slab';
    const MERCHANT_ID = 'merchant_id';
    const TYPE        = 'type';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';
    const DELETED_AT  = 'deleted_at';

    protected $entity = 'merchant_slabs';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::SLAB,
        self::TYPE,
        self::MERCHANT_ID,
    ];

    protected $public = [
        self::ID,
        self::SLAB,
        self::MERCHANT_ID,
        self::TYPE,
        self::CREATED_AT,
    ];

    protected $dates = [
        self::UPDATED_AT,
        self::CREATED_AT,
        self::DELETED_AT,
    ];

    protected $casts = [
        self::SLAB => 'array',
    ];

    public function merchant()
    {
        $this->belongsTo(Merchant\Entity::class, Merchant\Entity::ID, self::MERCHANT_ID);
    }

    public function getSlab()
    {
        return $this->getAttribute(self::SLAB);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }
}
