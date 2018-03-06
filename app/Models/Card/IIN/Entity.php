<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Bank\Name;
use RZP\Models\Bank\IFSC;

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
    const FLOWS         = 'flows';
    const ENABLED       = 'enabled';
    const LOCKED        = 'locked';

    const INTERNATIONAL = 'international';

    const ID_LENGTH = 6;
    const COUNTRY_LENGTH = 2;

    protected $entity = 'iin';

    protected $primaryKey = self::IIN;

    protected $appends = [self::INTERNATIONAL];

    protected static $modifiers = ['inputRemoveBlanks'];

    protected static $generators = [
        self::ISSUER_NAME
    ];

    protected $fillable = [
        self::IIN,
        self::CATEGORY,
        self::NETWORK,
        self::TYPE,
        self::COUNTRY,
        self::ISSUER,
        self::ISSUER_NAME,
        self::TRIVIA,
        self::EMI,
        self::ENABLED,
        self::FLOWS,
        self::LOCKED,
    ];

    protected $public = [
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
        self::ENABLED,
        self::FLOWS,
        self::LOCKED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::FLOWS,
    ];

    protected $defaults = [
        self::EMI      => false,
        self::FLOWS    => [
            '3ds' => '1'
        ],
        self::ENABLED  => true,
        self::LOCKED   => false
    ];

    protected $casts = [
        self::ENABLED => 'bool',
        self::LOCKED  => 'bool'
    ];

    public function supports($flows)
    {
        return (($this->getFlows() & $flows) === $flows);
    }

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

    public function isMagicEnabled()
    {
        return Flow::isApplicable(Flow::MAGIC, $this->getFlows());
    }

    protected function getNetworkCodeAttribute()
    {
        return Card\Network::getCode($this->getNetwork());
    }

    public function getTrivia()
    {
        return $this->getAttribute(self::TRIVIA);
    }

    public function getFlows()
    {
        return $this->getAttribute(self::FLOWS);
    }

    public function isEnabled()
    {
        return $this->getAttribute(self::ENABLED);
    }

    public function isLocked()
    {
        return $this->getAttribute(self::LOCKED);
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

    public function getIssuerName()
    {
        $dbName = $this->getAttribute(self::ISSUER_NAME);

        $issuer = $this->getAttribute(self::ISSUER);

        if (IFSC::exists($issuer) === true)
        {
            return Name::getName($issuer) ?? $dbName;
        }

        return $dbName;
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

    protected function setPublicFlowsAttribute(array & $array)
    {
        if (isset($array[self::FLOWS]) === true)
        {
            $array[self::FLOWS] = Flow::getEnabledFlows($array[self::FLOWS]);
        }
    }

    protected function setFlowsAttribute($flows)
    {
        $this->attributes[self::FLOWS] = Flow::getHexValue($flows);
    }

    protected function generateIssuerName($input)
    {
        if ((isset($input[self::ISSUER]) === true) and
            (isset($input[self::ISSUER_NAME]) === false))
        {
            $this->setAttribute(self::ISSUER_NAME, Name::getName($input[self::ISSUER]));
        }
    }
}
