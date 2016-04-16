<?php

namespace Models\Card;

use EE\Exception;
use Models\Card;
use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const GLOBAL_CARD_ID    = 'global_card_id';
    const NAME              = 'name';
    const EXPIRY_MONTH      = 'expiry_month';
    const EXPIRY_YEAR       = 'expiry_year';
    const IIN               = 'iin';
    const LAST4             = 'last4';
    const LENGTH            = 'length';
    const NETWORK           = 'network';
    const TYPE              = 'type';
    const EMI               = 'emi';
    const ISSUER            = 'issuer';
    const COUNTRY           = 'country';
    const INTERNATIONAL     = 'international';
    const VAULT_TOKEN       = 'vault_token';
    const VAULT             = 'vault';
    const TRIVIA            = 'trivia';

    /**
     * Number and cvv are never saved in the database
     * but are referenced at various points
     * and the values are held in-memory.
     */
    const NUMBER            = 'number';
    const CVV               = 'cvv';

    const COUNTRY_LENGTH = 2;

    const NETWORK_CODE      = 'network_code';

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
        self::EMI,
        self::TYPE,
        self::ISSUER,
        self::VAULT_TOKEN,
        self::VAULT,
        self::INTERNATIONAL,
    );

    protected $guarded = array(self::ID);

    protected static $modifiers = array('expiry_year', 'expiry_month', 'number');

    protected static $generators = array(
        self::ID,
        self::IIN,
        self::TYPE,
        self::LAST4,
        self::LENGTH);

    protected $hidden = array();

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::GLOBAL_CARD_ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::IIN,
        self::LAST4,
        self::LENGTH,
        self::NETWORK,
        self::TYPE,
        self::EMI,
        self::ISSUER,
        self::COUNTRY,
        self::INTERNATIONAL,
        self::VAULT_TOKEN,
        self::VAULT,
        self::NETWORK_CODE,
        self::TRIVIA,
    );

    protected $public = array(
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::LAST4,
        self::NETWORK,
        self::EMI,
        self::ISSUER,
        self::COUNTRY,
        self::INTERNATIONAL,
    );

    protected $appends = array(
        self::NETWORK_CODE);

    protected $defaults = array(
        self::INTERNATIONAL     => null,
        self::EMI               => false,
        self::GLOBAL_CARD_ID    => null,
    );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function iinRelation()
    {
        return $this->belongsTo('Models\Card\IIN\Entity', 'iin', 'iin');
    }

    public function globalCard()
    {
        return $this->belongsTo('Models\Card\Entity', self::GLOBAL_CARD_ID, self::ID);
    }

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

    public function generateType($input)
    {
        $this->setAttribute(self::TYPE, Card\Type::UNKNOWN);
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

    public function getNetworkCode()
    {
        return $this->getNetworkCodeAttribute();
    }

    public function getNetworkCodeAttribute()
    {
        return Card\Network::getCode($this->getNetwork());
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getLast4()
    {
        return $this->getAttribute(self::LAST4);
    }

    public function getVaultToken()
    {
        return $this->getAttribute(self::VAULT_TOKEN);
    }

    public function getVault()
    {
        return $this->getAttribute(self::VAULT);
    }

    public function getExpiryMonth()
    {
        return $this->getAttribute(self::EXPIRY_MONTH);
    }

    public function getExpiryYear()
    {
        return $this->getAttribute(self::EXPIRY_YEAR);
    }

    public function setCountry($country)
    {
        $this->setAttribute(self::COUNTRY, $country);

        if ($country === 'IN')
        {
            $this->setAttribute(self::INTERNATIONAL, 0);
        }
    }

    public function setNetwork($network)
    {
        $this->setAttribute(self::NETWORK, $network);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setInternational($flag)
    {
        $this->setAttribute(self::INTERNATIONAL, $flag);
    }

    public function setEmi($flag)
    {
        $this->setAttribute(self::EMI, $flag);
    }

    public function setTrivia($trivia)
    {
        $this->setAttribute(self::TRIVIA, $trivia);
    }

    public function getIin()
    {
        return $this->getAttribute(self::IIN);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getEmi()
    {
        return (bool) $this->getAttribute(self::EMI);
    }

    public function getExpiryMonthAttribute()
    {
        return (int) $this->getAttributeFromArray(self::EXPIRY_MONTH);
    }

    public function getExpiryYearAttribute()
    {
        return (int) $this->getAttributeFromArray(self::EXPIRY_YEAR);
    }

    public function getInternationalAttribute()
    {
        $intl = $this->attributes[self::INTERNATIONAL];

        if ($intl === null)
        {
            return;
        }

        return (bool) $this->attributes[self::INTERNATIONAL];
    }

    public function isUnsupported()
    {
        $network = Card\Network::getCode($this->getNetwork());

        return (Card\Network::isUnsupportedNetwork($network));
    }

    public function isInternational()
    {
        return (bool) $this->getAttribute(self::INTERNATIONAL);
    }

    public function getFormatted()
    {
        return 'XXXX-XXXX-XXXX-'.$this->getLast4();
    }

    public function getCountry()
    {
        return $this->getAttribute(self::COUNTRY);
    }
}
