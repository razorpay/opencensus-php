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

    // TODO:
    // - Post https://github.com/razorpay/api/pull/2994 is reviewed and moved
    //   following($expands) will work.
    //
    // - Remove $appends and getTaxesAttribute().
    // - Remove TAXES from visible.

    protected $expands = [
        self::TAXES,
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
        // Please note that this pivot table doesn't have soft delete. Laravel
        // does not support(https://github.com/laravel/framework/issues/2733)
        // this for some reason.
        //
        // We can do work around(more code) to have that, but I think we can live
        // without it for pivot tables.

        return $this->belongsToMany('RZP\Models\Tax\Entity', 'tax_group_tax_map')
                    ->withTimestamps();
    }
}
