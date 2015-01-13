<?php

namespace Models\Card;

use EE\Exception;
use Models\Card;
use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID                = 'id';
    const NAME              = 'name';
    const EXPIRY_MONTH      = 'expiry_month';
    const EXPIRY_YEAR       = 'expiry_year';
    const IIN               = 'iin';
    const LAST4             = 'last4';
    const LENGTH            = 'length';
    const NETWORK           = 'network';
    const TYPE              = 'type';
    const ISSUER            = 'issuer';
    const COUNTRY           = 'country';
    const ADDRESS_LINE1     = 'address_line1';
    const ADDRESS_LINE2     = 'address_line2';
    const ADDRESS_CITY      = 'address_city';
    const ADDRESS_STATE     = 'address_state';
    const ADDRESS_ZIP       = 'address_zip';
    const ADDRESS_COUNTRY   = 'address_country';

    const COUNTRY_LENGTH = 2;

    protected $table = \Constants\Table::CARD;

    protected static $sign = 'card';

    protected $entity = 'card';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::NETWORK,
        self::COUNTRY,
        self::TYPE,
        self::ISSUER,
        self::ADDRESS_LINE1,
        self::ADDRESS_LINE2,
        self::ADDRESS_STATE,
        self::ADDRESS_CITY,
        self::ADDRESS_ZIP,
        self::ADDRESS_COUNTRY);

    protected $guarded = array(self::ID);

    protected static $modifiers = array('expiry_year', 'expiry_month', 'number');

    protected static $generators = array(
        self::ID,
        self::IIN,
        self::LAST4,
        self::LENGTH);

    protected $visible = array(
        self::ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::IIN,
        self::LAST4,
        self::LENGTH,
        self::NETWORK);

    public function generateLast4($input)
    {
        $last4 = substr($input['number'], -4);

        $this->setAttribute(self::LAST4, $last4);
    }

    public function generateIin($input)
    {
        $iin = substr($input['number'], 0, 6);

        $this->setAttribute(self::IIN, $iin);
    }

    public function generateLength($input)
    {
        $length = strlen($input['number']);

        $this->setAttribute(self::LENGTH, $length);
    }

    public function modifyExpiryYear(& $input)
    {
        if ((isset($input['expiry_year'])) and
            (strlen($input['expiry_year']) == 2))
        {
            $input['expiry_year'] = '20'.$input['expiry_year'];
        }
    }

    public function modifyExpiryMonth(& $input)
    {
        if (isset($input['expiry_month']))
        {
            $input['expiry_month'] = ltrim($input['expiry_month'], '0');
        }
    }

    public static function modifyNumber(& $input)
    {
        $number = $input['number'];

        if (is_string($number) === false)
        {
            return $number;
        }

        $number = str_replace(' ', '', $number);
        $number = str_replace('-', '', $number);

        $input['number'] = $number;
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function setCountry($country)
    {
        $this->setAttribute(self::COUNTRY, $country);
    }

    public function setNetwork($network)
    {
        $this->setAttribute(self::NETWORK, $network);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function getIin()
    {
        return $this->getAttribute(self::IIN);
    }

    public function getExpiryMonthAttribute()
    {
        return (int) $this->getAttributeFromArray(self::EXPIRY_MONTH);
    }

    public function getExpiryYearAttribute()
    {
        return (int) $this->getAttributeFromArray(self::EXPIRY_YEAR);
    }

    public function isUnsupported()
    {
        $network = Card\Network::getCode($this->getNetwork());

        return (Card\Network::isUnsupportedNetwork($network));
    }
}
