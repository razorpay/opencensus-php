<?php

namespace Models\Customer;

use Models\Base;
use Models\Base\Traits\NotesTrait;
use Models\Merchant\Account;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const NAME              =       'name';
    const CONTACT           =       'contact';
    const EMAIL             =       'email';
    const MERCHANT_ID       =       'merchant_id';
    const ACTIVE            =       'active';
    const NOTES             =       'notes';

    protected static $sign      = 'cust';

    protected $entity           = 'customer';

    protected $table            = \Constants\Table::CUSTOMER;

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
        self::NOTES,
        self::CONTACT,
        self::CREATED_AT,
    );

    protected $defaults = array(
        self::ACTIVE    => true,
        self::NOTES     => [],
    );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
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

    public function getActiveAttribute()
    {
        return (bool)$this->attributes[self::ACTIVE];
    }

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
    }
}