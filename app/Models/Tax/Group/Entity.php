<?php

namespace RZP\Models\Tax\Group;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    // Table attributes

    const MERCHANT_ID = 'merchant_id';
    const NAME        = 'name';

    // Request input keys
    const TAX_IDS     = 'tax_ids';

    protected static $sign = 'taxg';

    protected $entity = 'tax_group';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::MERCHANT_ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::NAME,
    ];

    // Getters

    public function getName(): string
    {
        return $this->getAttribute(self::NAME);
    }

    // Model relations

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function taxes()
    {
        return $this->belongsToMany('RZP\Models\Tax\Entity', 'tax_group_tax_map')
                    ->withTimestamps();
    }
}
