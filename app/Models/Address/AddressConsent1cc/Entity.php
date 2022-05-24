<?php

namespace RZP\Models\Address\AddressConsent1cc;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;
use RZP\Models\Customer;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID             = 'id';
    const CUSTOMER_ID    = 'customer_id';
    const DEVICE_ID      = 'device_id';


    protected $entity = 'address_consent_1cc';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    // Added this to prevent errors on absence of updated_at field
    const UPDATED_AT = null;

    protected $fillable = [
        self::CUSTOMER_ID,
        self::DEVICE_ID,
    ];

    protected $public = [
        self::ID,
        self::CUSTOMER_ID,
        self::DEVICE_ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::DELETED_AT,
    ];

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getDeviceId()
    {
        return $this->getAttribute(self::DEVICE_ID);
    }

    public function setDeviceId(string $deviceId)
    {
        $this->setAttribute(self::DEVICE_ID, $deviceId);
    }

    public function setCustomerId(string $customerId)
    {
        $this->setAttribute(self::CUSTOMER_ID, $customerId);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer\Entity::class, Base\UniqueIdEntity::ID, self::CUSTOMER_ID);
    }
}
