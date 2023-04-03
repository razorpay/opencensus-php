<?php

namespace RZP\Models\Merchant\Merchant1ccConfig;

use RZP\Models\Base;
use RZP\Models\Merchant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID          = 'id';
    const MERCHANT_ID = 'merchant_id';
    const CONFIG      = 'config';
    const VALUE       = 'value';
    const VALUE_JSON  = 'value_json';

    protected $entity = 'merchant_1cc_configs';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::CONFIG,
        self::VALUE,
        self::VALUE_JSON,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::CONFIG,
        self::VALUE,
        self::VALUE_JSON,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $casts              = [
        self::VALUE_JSON  => 'array',
    ];

    protected $defaults           = [
        self::VALUE_JSON  => [],
    ];


    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant\Entity::class, Merchant\Entity::ID, self::MERCHANT_ID);
    }

    public function getConfig()
    {
        return $this->getAttributeValue(self::CONFIG);
    }

    public function getValue()
    {
        return $this->getAttributeValue(self::VALUE);
    }

    public function getValueJson()
    {
        return $this->getAttributeValue(self::VALUE_JSON);
    }

    public function setConfig(string $value)
    {
        $this->setAttribute(self::CONFIG, $value);
    }

    public function setValue(string $value)
    {
        $this->setAttribute(self::VALUE, $value);
    }
}
