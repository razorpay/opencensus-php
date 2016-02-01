<?php

namespace Models\Card\IIN;

use Models\Base;
use Constants\Table;

class Entity extends Base\PublicEntity
{
    const IIN           = 'iin';
    const CATEGORY      = 'category';
    const NETWORK       = 'network';
    const TYPE          = 'type';
    const COUNTRY       = 'country';
    const ISSUER        = 'issuer';
    const ISSUER_NAME   = 'issuer_name';
    const EMI           = 'emi';
    const TRIVIA        = 'trivia';

    const INTERNATIONAL = 'international';

    const ID_LENGTH = 6;
    const COUNTRY_LENGTH = 2;

    protected $entity = 'iin';

    protected $table = Table::IIN;

    protected $primaryKey = self::IIN;

    public $timestamps = false;

    protected $appends = array(self::INTERNATIONAL);

    protected $fillable = array(
        self::IIN,
        self::CATEGORY,
        self::NETWORK,
        self::TYPE,
        self::COUNTRY,
        self::ISSUER,
        self::ISSUER_NAME,
        self::TRIVIA,
        self::EMI
    );

    protected $public = array(
        self::IIN,
        self::ENTITY,
        self::CATEGORY,
        self::NETWORK,
        self::TYPE,
        self::COUNTRY,
        self::ISSUER,
        self::ISSUER_NAME,
        self::EMI,
        self::TRIVIA,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $defaults = array(
        self::EMI       =>  0,
    );

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getCountry()
    {
        return $this->getAttribute(self::COUNTRY);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::BRAND);
    }

    public function getIinAttribute()
    {
        return (int) $this->attributes[self::IIN];
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getIssuerAttribute()
    {
        return $this->attributes[self::ISSUER];
    }

    public function getInternationalAttribute()
    {
        $country = $this->getCountry();

        return ($country !== 'IN');
    }
}