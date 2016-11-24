<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Base;
use RZP\Models\Card;

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
    const OTP_READ      = 'otp_read';
    const TRIVIA        = 'trivia';

    const INTERNATIONAL = 'international';

    const ID_LENGTH = 6;
    const COUNTRY_LENGTH = 2;

    protected $entity = 'iin';

    protected $primaryKey = self::IIN;

    protected $appends = array(self::INTERNATIONAL);

    protected static $modifiers = array('inputRemoveBlanks');

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
        self::OTP_READ,
        self::TRIVIA,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $defaults = array(
        self::EMI => false,
    );

    public function isEmiAvailable()
    {
        return $this->getAttribute(self::EMI);
    }

    public function isInternational()
    {
        return $this->getInternationalAttribute();
    }

    public function getIin()
    {
        return $this->getAttribute(self::IIN);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function setType($type)
    {
        Card\Type::checkType($type);
        $this->setAttribute(self::TYPE, $type);
    }

    public function setCountry($countryCode)
    {
        $this->setAttribute(self::COUNTRY, $countryCode);
    }

    public function getCountry()
    {
        return $this->getAttribute(self::COUNTRY);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getNetworkCode()
    {
        return $this->getNetworkCodeAttribute();
    }

    protected function getNetworkCodeAttribute()
    {
        return Card\Network::getCode($this->getNetwork());
    }

    public function getTrivia()
    {
        return $this->getAttribute(self::TRIVIA);
    }

    public function setTrivia($trivia)
    {
        $this->setAttribute(self::TRIVIA, $trivia);
    }

    protected function getIinAttribute()
    {
        return (int) $this->attributes[self::IIN];
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function setIssuer($issuer)
    {
        $this->setAttribute(self::ISSUER, $issuer);
    }

    public function getOtpRead()
    {
        return $this->getAttribute(self::OTP_READ);
    }

    public function setOtpRead($flag)
    {
        $this->setAttribute(self::OTP_READ, $flag);
    }

    protected function getOtpReadAttribute()
    {
        return (bool) $this->attributes[self::OTP_READ];
    }

    protected function getIssuerAttribute()
    {
        return $this->attributes[self::ISSUER];
    }

    protected function getInternationalAttribute()
    {
        $country = $this->getCountry();

        return ($country !== 'IN');
    }

    protected function getEmiAttribute()
    {
        return (bool) $this->attributes[self::EMI];
    }
}