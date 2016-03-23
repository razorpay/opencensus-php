<?php

namespace Models\Customer;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const NAME              =       'name';
    const EMAIL             =       'email';
    const CONTACT           =       'contact';
    const MERCHANT_ID       =       'merchant_id';
    const ACTIVE            =       'active';

    protected static $sign      = '';

    protected $entity           = 'customer';

    protected $table            = \Constants\Table::CUSTOMER;

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::ACTIVE,
        self::CONTACT,
        self::MERCHANT_ID,
    );

    protected $visible = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::ACTIVE,
        self::CONTACT,
        self::MERCHANT_ID,
    );

    protected $public = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::ACTIVE,
        self::CONTACT
    );

    protected $defaults = array(
        self::ACTIVE    =>  true,
    );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function getName()
    {
        return $this->attributes(self::NAME);
    }

    public function getEmail()
    {
        return $this->attributes(self::EMAIL);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function isActive()
    {
        return (bool)$this->getAttribute(self::ACTIVE);
    }

    public function getActiveAttribute()
    {
        return (bool)$this->attributes[self::ACTIVE];
    }
}