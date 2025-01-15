<?php

namespace RZP\Models\Address\AddressConsent1cc;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Constants\Entity as ConstantsEntity;
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

    /**
     * Relations to ignore while checking existence of associated entities
     * while saving current entity.
     *
     * @var array
     */
    protected $ignoredRelations = [
        // Required as customer entity will be created via CMS and may not be present in API DB
        ConstantsEntity::CUSTOMER,
    ];

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
