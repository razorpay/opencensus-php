<?php

namespace RZP\Models\Merchant\OwnerDetail;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                                = "id";
    const MERCHANT_ID                       = "merchant_id";
    const GATEWAY                           = "gateway";
    const OWNER_DETAILS                     = "owner_details";
    const CREATED_AT                        = "created_at";
    const UPDATED_AT                        = "updated_at";
    const DELETED_AT                        = "deleted_at";


    protected $entity      = 'merchant_owner_details';

    protected $primaryKey  = self::ID;

    protected $fillable    = [
        self::MERCHANT_ID,
        self::GATEWAY,
        self::OWNER_DETAILS,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public      = [
        self::ID,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::OWNER_DETAILS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $dates        = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults     = [
        self::GATEWAY         => null,
        self::OWNER_DETAILS   => [],
        self::UPDATED_AT      => null,
        self::DELETED_AT      => null,
    ];

    protected $casts        = [
        self::OWNER_DETAILS   => 'array' ,
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getOwnerDetails()
    {
        return $this->getAttribute(self::OWNER_DETAILS);
    }

    public function setOwnerDetails($ownerDetails)
    {
        return $this->setAttribute(self::OWNER_DETAILS, $ownerDetails);
    }
}
