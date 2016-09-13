<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const NAME                  = 'name';
    const CONTACT               = 'contact';
    const EMAIL                 = 'email';
    const MERCHANT_ID           = 'merchant_id';
    const SHIPPING_ADDRESS_ID   = 'shipping_address_id';
    const ACTIVE                = 'active';
    const NOTES                 = 'notes';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    const SHIPPING_ADDRESS      = 'shipping_address';

    protected static $sign      = 'cust';

    protected $entity           = 'customer';

    protected $table            = Table::CUSTOMER;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::NOTES,
        self::ACTIVE,
        self::CONTACT,
        self::MERCHANT_ID,
    );

    protected $visible = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::NOTES,
        self::ACTIVE,
        self::CONTACT,
        self::SHIPPING_ADDRESS_ID,
        self::NOTES,
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    );

    protected $public = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::SHIPPING_ADDRESS_ID,
        self::CREATED_AT,
    );

    protected $defaults = array(
        self::ACTIVE    => true,
        self::NOTES     => [],
    );

    // ----------------------------------- GETTERS -----------------------------------

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    // ----------------------------------- END GETTERS -----------------------------------

    // ----------------------------------- ACCESSORS -----------------------------------

    protected function getActiveAttribute()
    {
        return (bool) $this->attributes[self::ACTIVE];
    }

    // ----------------------------------- END ACCESSORS -----------------------------------

    // ----------------------------------- SETTERS -----------------------------------

    public function setShippingAddressId($shippingAddressId)
    {
        $this->setAttribute(self::SHIPPING_ADDRESS_ID, $shippingAddressId);
    }

    // -----------------------------------  END SETTERS -----------------------------------

    // ----------------------------------- MUTATORS -----------------------------------

    protected function setEmailAttribute($email)
    {
        // Multi-byte function to handle unicode
        $this->attributes[self::EMAIL] = mb_strtolower($email);
    }

    // ----------------------------------- END MUTATORS -----------------------------------

    // ----------------------------------- RELATIONS -----------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function tokens()
    {
        return $this->hasMany('RZP\Models\Customer\Token\Entity');
    }

    // public function shippingAddresses()
    // {
    //     return $this->hasMany('RZP\Models\Customer\Address\Entity', self::SHIPPING_ADDRESS_ID, Address\Entity::ENTITY_ID);
    //                 //->where(Address\Entity::ENTITY_TYPE, '=', Address\Type::CUSTOMER);
    // }

    // ----------------------------------- END RELATIONS -----------------------------------
}
