<?php

namespace RZP\Models\Customer\CustomerConsent1cc;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID             = 'id';
    const CONTACT        = 'contact';
    const MERCHANT_ID    = 'merchant_id';
    const STATUS         = 'status';


    protected $entity = 'customer_consent_1cc';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::CONTACT,
        self::MERCHANT_ID,
        self::STATUS,
    ];

    protected $public = [
        self::CONTACT,
        self::MERCHANT_ID,
        self::STATUS
    ];

    protected $dates = [
        self::CREATED_AT,
        self::DELETED_AT,
        self::UPDATED_AT,
    ];

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setContact(string $contact)
    {
        $this->setAttribute(self::CONTACT, $contact);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant\Entity::class, Base\UniqueIdEntity::ID, self::MERCHANT_ID);
    }
}
