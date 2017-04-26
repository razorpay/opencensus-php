<?php

namespace RZP\Models\Tax\Group;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    // Table attributes

    const MERCHANT_ID = 'merchant_id';
    const NAME        = 'name';

    // Request/input keys
    const TAX_IDS     = 'tax_ids';

    // Response/output keys
    const TAXES       = 'taxes';


    protected static $sign = 'taxg';

    protected $entity = 'tax_group';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::MERCHANT_ID,
        self::NAME,
        self::TAXES,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::TAXES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::NAME,
    ];

    protected $appends = [
        self::PUBLIC_ID,
        self::ENTITY,
        self::TAXES,
    ];

    // Getters

    public function getName(): string
    {
        return $this->getAttribute(self::NAME);
    }

    // Custom attributes accessors

    public function getTaxesAttribute()
    {
        return $this->taxes()->getResults()->toArrayPublicEmbedded();
    }

    // Model relations

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function taxes()
    {
        return $this->belongsToMany('RZP\Models\Tax\Entity', Table::TAX_GROUP_TAX_MAP)
                    ->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany('RZP\Models\Item\Entity');
    }
}
