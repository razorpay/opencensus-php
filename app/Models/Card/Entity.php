<?php

namespace RZP\Models\Card;

use RZP\Models\Card;
use RZP\Models\Base;
use RZP\Exception;

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

    protected $table = \RZP\Constants\Table::CARD;

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
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::NAME,
        self::LAST4,
        self::NETWORK,
        self::INTERNATIONAL,
    );

    protected $appends = array(
        self::NETWORK_CODE);

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::EMI);

    protected $defaults = array(
        self::INTERNATIONAL     => null,
        self::EMI               => false,
        self::GLOBAL_CARD_ID    => null,
        self::VAULT             => null,
        self::VAULT_TOKEN       => null,
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function iinRelation()
    {
        return $this->belongsTo('RZP\Models\Card\IIN\Entity', 'iin', 'iin');
    }

    public function globalCard()
    {
        return $this->belongsTo('RZP\Models\Card\Entity', self::GLOBAL_CARD_ID, self::ID);
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

    public function getNetworkColorCode()
    {
        return Card\Network::getColorCode($this->getNetworkCode());
    }

    protected function getNetworkCodeAttribute()
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

    public function getTypeElseDefault()
    {
        // Fee based on the method type
        $cardType = $this->getType();

        if ($cardType === Card\Type::UNKNOWN)
        {
            $cardType = Card\Type::DEBIT;
        }

        return $cardType;
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

    public function setVaultToken($vaultToken)
    {
        $this->setAttribute(self::VAULT_TOKEN, $vaultToken);
    }

    public function setVault($vault)
    {
        $this->setAttribute(self::VAULT, $vault);
    }

    public function setTrivia($trivia)
    {
        $this->setAttribute(self::TRIVIA, $trivia);
    }

    protected function setPublicEmiAttribute(array & $array)
    {
        $array[self::ISSUER] = null;
        $array[self::EMI] = $this->getEmi();

        if ($this->getEmi() === true)
        {
            $array[self::ISSUER] = $this->getIssuer();
        }
        else
        {
            unset($array[self::ISSUER]);
        }
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

    protected function getExpiryMonthAttribute()
    {
        return (int) $this->getAttributeFromArray(self::EXPIRY_MONTH);
    }

    protected function getExpiryYearAttribute()
    {
        return (int) $this->getAttributeFromArray(self::EXPIRY_YEAR);
    }

    protected function getInternationalAttribute()
    {
        $intl = $this->attributes[self::INTERNATIONAL];

        if ($intl === null)
        {
            return null;
        }

        return (bool) $this->attributes[self::INTERNATIONAL];
    }

    protected function getEmiAttribute()
    {
        return (bool) $this->attributes[self::EMI];;
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

    public function isAmex()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::AMEX]);
    }

    public function isRuPay()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::RUPAY]);
    }

    public function isRecurringSupported()
    {
        $isCreditCard = ($this->getType() === Card\Type::CREDIT);

        $isSupportedNetwork = in_array($this->getNetworkCode(), Card\Network::$recurringNetworks);

        return (($isCreditCard == true) and ($isSupportedNetwork == true));
    }

    public function isBlocked()
    {
        $iin = $this->getIin();

        $last4 = $this->getLast4();

        $blackList = Card\BlackList::BLOCKED_IIN_LAST4;

        if ((isset($blackList[$iin]) === true) and
            (in_array($last4, $blackList[$iin])))
        {
            return true;
        }

        return false;
    }

    protected function getTokenRelevantAttributes()
    {
        $emi = $this->getAttribute(self::EMI);

        $attributes = array(
            self::EXPIRY_MONTH => $this->getAttribute(self::EXPIRY_MONTH),
            self::EXPIRY_YEAR  => $this->getAttribute(self::EXPIRY_YEAR),
            self::EMI          => $emi
        );

        if ($emi === true)
        {
            $attributes[self::ISSUER] = $this->getIssuer();
        }

        return $attributes;
    }

    public function toArrayToken()
    {
        $attributes = $this->toArrayPublic();

        $attributes = array_merge($attributes, $this->getTokenRelevantAttributes());

        unset($attributes[self::ID]);

        return $attributes;
    }
}
