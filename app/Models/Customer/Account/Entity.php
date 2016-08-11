<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
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
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'cust';

    protected $entity           = 'customer';

    protected $table            = \RZP\Constants\Table::CUSTOMER;

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
        self::MERCHANT_ID,
    );

    protected $public = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::CREATED_AT,
    );

    protected $defaults = array(
        self::ACTIVE    => true,
        self::NOTES     => [],
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function tokens()
    {
        return $this->hasMany('RZP\Models\Customer\Token\Entity');
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
        return (bool)$this->getAttribute(self::ACTIVE);
    }

    protected function getActiveAttribute()
    {
        return (bool)$this->attributes[self::ACTIVE];
    }

    protected function setEmailAttribute($email)
    {
        // Multi-byte function to handle unicode
        $this->attributes[self::EMAIL] = mb_strtolower($email);
    }

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
    }
}
