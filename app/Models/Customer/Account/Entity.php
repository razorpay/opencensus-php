<?php

namespace RZP\Models\Customer;

use App;
use RZP\Models\Base;
use RZP\Models\Address;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const NAME                  = 'name';
    const CONTACT               = 'contact';
    const EMAIL                 = 'email';
    const MERCHANT_ID           = 'merchant_id';
    const ACTIVE                = 'active';
    const NOTES                 = 'notes';
    const ADDRESS               = 'address';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    const SHIPPING_ADDRESS      = 'shipping_address';

    protected static $sign      = 'cust';

    protected $entity           = 'customer';

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::NOTES,
        self::ACTIVE,
        self::CONTACT,
        self::ADDRESS,
        self::MERCHANT_ID,
    );

    protected $visible = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::NOTES,
        self::ACTIVE,
        self::CONTACT,
        self::SHIPPING_ADDRESS,
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
        self::SHIPPING_ADDRESS,
        self::CREATED_AT,
    );

    protected $defaults = array(
        self::ACTIVE    => true,
        self::NOTES     => [],
    );

    protected $appends = array(
        self::SHIPPING_ADDRESS);

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::SHIPPING_ADDRESS);

    // ----------------------------------- GETTERS -----------------------------------

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
    }

    public function invoices()
    {
        return $this->hasMany('RZP\Models\Invoice\Entity');
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
    
    public function getCurrentShippingAddress()
    {
        $app = App::getFacadeRoot();
        
        $shippingAddress = $app['repo']->address
            ->fetchPrimaryAddressOfEntityOfType($this, Address\Type::SHIPPING_ADDRESS);
        
        return $shippingAddress;
    }

    // ----------------------------------- END GETTERS -----------------------------------

    // ----------------------------------- ACCESSORS -----------------------------------

    protected function getActiveAttribute()
    {
        return (bool) $this->attributes[self::ACTIVE];
    }

    protected function getShippingAddressAttribute()
    {
        $input[Address\Entity::TYPE] = Address\Type::SHIPPING_ADDRESS;

        $app = App::getFacadeRoot();

        $shippingAddresses = $app['repo']->address->fetchAddressesForEntity($this, $input);

        if ($shippingAddresses->count() === 0)
        {
            return null;
        }

        return $shippingAddresses->toArrayPublicEmbedded();
    }

    // ----------------------------------- END ACCESSORS -----------------------------------

    // ----------------------------------- PUBLIC SETTERS -----------------------------------

    public function setPublicShippingAddressAttribute(array & $array)
    {
        if (empty($array[self::SHIPPING_ADDRESS]) === true)
        {
            unset($array[self::SHIPPING_ADDRESS]);
        }
    }

    // ----------------------------------- END PUBLIC SETTERS -----------------------------------

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

    // ----------------------------------- END RELATIONS -----------------------------------
}
