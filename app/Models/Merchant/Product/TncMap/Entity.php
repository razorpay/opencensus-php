<?php

namespace RZP\Models\Merchant\Product\TncMap;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const PRODUCT_NAME              = 'product_name';
    const BUSINESS_UNIT             = 'business_unit';
    const CONTENT                   = 'content';
    const STATUS                    = 'status';

    protected $entity               = 'tnc_map';

    protected $generateIdOnCreate   = true;

    protected  $primaryKey          = self::ID;

    protected static $sign          = 'tnc_map';

    protected $fillable = [
        self::PRODUCT_NAME,
        self::BUSINESS_UNIT,
        self::STATUS,
        self::CONTENT,
    ];

    protected $public = [
        self::ID,
        self::STATUS,
        self::CONTENT
    ];

    protected $casts = [
        self::CONTENT => 'array'
    ];

    protected $defaults = [
        self::STATUS => 'active'
    ];

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function getProductName()
    {
        return $this->getAttribute(self::PRODUCT_NAME);
    }

    public function getBusinessUnit()
    {
        return $this->getAttribute(self::BUSINESS_UNIT);
    }

    public function getContent()
    {
        return $this->getAttribute(self::CONTENT);
    }
}
