<?php

namespace RZP\Models\Card;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Card;
use RZP\Models\Base;
use RZP\Models\Merchant;

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

    const DUMMY_EXPIRY_YEAR  = '2021';
    const DUMMY_EXPIRY_MONTH = '12';
    const DUMMY_CVV          = '123';

    const NETWORK_CODE      = 'network_code';

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
        self::LENGTH,
        self::VAULT_TOKEN);

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

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::LAST4,
        self::NETWORK,
        self::TYPE,
        self::ISSUER,
        self::INTERNATIONAL,
        self::EMI,
    ];

    protected $appends = [self::NETWORK_CODE];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

    protected $defaults = [
        self::INTERNATIONAL     => null,
        self::EMI               => false,
        self::GLOBAL_CARD_ID    => null,
        self::VAULT             => null,
        self::VAULT_TOKEN       => null,
    ];

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

    public function hasGlobalCard(): bool
    {
        return $this->isAttributeNotNull(self::GLOBAL_CARD_ID);
    }

    protected function generateLast4($input)
    {
        $last4 = substr($input['number'], -4);

        $this->setAttribute(self::LAST4, $last4);
    }

    protected function generateIin($input)
    {
        $iin = substr($input['number'], 0, 6);

        $this->setAttribute(self::IIN, $iin);
    }

    protected function generateType($input)
    {
        $this->setAttribute(self::TYPE, Card\Type::UNKNOWN);
    }

    protected function generateLength($input)
    {
        $length = strlen($input['number']);

        $this->setAttribute(self::LENGTH, $length);
    }

    protected function generateVaultToken($input)
    {
        if (isset($input[self::VAULT]))
        {
            $vaultToken = (new Card\Tokenex)->getVaultToken($input['number']);

            $this->setAttribute(self::VAULT_TOKEN, $vaultToken);
        }
    }

    public static function modifyMaestro(& $input)
    {
        $iin = substr($input['number'] ?? null, 0, 6);
        $cardNetwork = Network::detectNetwork($iin);

        if ($cardNetwork === Network::MAES)
        {
            if (empty($input[Entity::EXPIRY_YEAR]) === true)
            {
                $input[Entity::EXPIRY_YEAR] = self::DUMMY_EXPIRY_YEAR;
            }

            if (empty($input[Entity::EXPIRY_MONTH]) === true)
            {
                $input[Entity::EXPIRY_MONTH] = self::DUMMY_EXPIRY_MONTH;
            }

            if (empty($input[Entity::CVV]) === true)
            {
                $input[Entity::CVV] = self::DUMMY_CVV;
            }
        }
    }

    public function modifyExpiryYear(& $input)
    {
        if ((isset($input['expiry_year'])) and
            (strlen($input['expiry_year']) === 2))
        {
            $input['expiry_year'] = '20' . $input['expiry_year'];
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
        if (isset($input['number']))
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

    public function getFirstName()
    {
        $name = $this->getAttribute(self::NAME);

        $names = explode(' ', $name, 2);

        return $names[0];
    }

    public function getLastName()
    {
        $name = $this->getAttribute(self::NAME);

        $names = explode(' ', $name, 2);

        return $names[1] ?? '';
    }

    public function getType()
    {
        $type = $this->getAttribute(self::TYPE);

        if ($type === Type::UNKNOWN)
        {
            return Type::CREDIT;
        }

        return $type;
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

    public function getExpiryTimestamp()
    {
        $year = $this->getExpiryYear();

        $month = $this->getExpiryMonth();

        return Carbon::createFromDate($year, $month, 1, Timezone::IST)
                        ->endOfMonth()
                        ->getTimestamp();
    }

    public function getTypeElseDefault()
    {
        // Fee based on the method type
        $cardType = $this->getType();

        if ($cardType === Card\Type::UNKNOWN)
        {
            $cardType = Card\Type::CREDIT;
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

    public function getIin()
    {
        return $this->getAttribute(self::IIN);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function setPublicIssuerAttribute(array & $array)
    {
        //
        // Allowing only for policy bazaar and shared merchant account
        //
        $allowedMerchantIds = ['7LAuMvKMcy7s0f', Merchant\Account::SHARED_ACCOUNT];

        $cardMerchant = $this->getMerchantId();

        if (($this->getEmi() === false) and
            (in_array($cardMerchant, $allowedMerchantIds, true) === false))
        {
            unset($array[self::ISSUER]);
        }
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
        return (bool) $this->attributes[self::EMI];
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
        return 'XXXX-XXXX-XXXX-' . $this->getLast4();
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

    public function isMaestro()
    {
        $network = $this->getNetwork();

        return (Card\NetworkName::$codes[$network] === Card\Network::MAES);
    }

    public function isRuPay()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::RUPAY]);
    }

    public function isDebit()
    {
        $type = $this->getType();

        return ($type === Type::DEBIT);
    }

    public function isRecurringSupported()
    {
        $isCreditCard = ($this->getType() === Card\Type::CREDIT);

        $isSupportedNetwork = in_array($this->getNetworkCode(), Card\Network::$recurringNetworks);

        return (($isCreditCard === true) and ($isSupportedNetwork === true));
    }

    public function isBlocked()
    {
        // if iin is missing from database, allow transaction on it
        if ($this->iinRelation === null)
        {
            return false;
        }

        $iin = $this->getIin();

        $last4 = $this->getLast4();

        if ($this->iinRelation->isEnabled() === false)
        {
            return true;
        }

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
        $attributes = array(
            self::EXPIRY_MONTH => $this->getExpiryMonth(),
            self::EXPIRY_YEAR  => $this->getExpiryYear(),
            self::EMI          => $this->getEmi(),
            self::ISSUER       => $this->getIssuer()
        );

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
