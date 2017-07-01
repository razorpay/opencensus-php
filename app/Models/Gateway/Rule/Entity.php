<?php

namespace RZP\Models\Gateway\Rule;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID      = 'merchant_id';
    const GATEWAY          = 'gateway';
    const TYPE             = 'type';
    const GROUP            = 'group';
    const FILTER_TYPE      = 'filter_type';
    const LOAD             = 'load';

    // Terminal properties
    const GATEWAY_ACQUIRER = 'gateway_acquirer';
    const NETWORK_CATEGORY = 'network_category';
    const TERMINAL_TYPE    = 'terminal_type';

    // Payment properties
    const METHOD           = 'method';
    const METHOD_TYPE      = 'method_type';
    const NETWORK          = 'network';
    const ISSUER           = 'issuer';
    const MIN_AMOUNT       = 'min_amount';
    const MAX_AMOUNT       = 'max_amount';
    const IINS             = 'iins';

    // Terminal and payment properties both
    const EMI_DURATION     = 'emi_duration';
    const INTERNATIONAL    = 'international';
    const CURRENCY         = 'currency';

    // Merchant properties
    const CATEGORY2        = 'category2';

    const DELETED_AT       = 'deleted_at';

    const MAX_LOAD = 10000;

    // Rule types
    const SORTER = 'sorter';
    const FILTER = 'filter';

    // Filter types
    const SELECT = 'select';
    const REJECT = 'reject';

    /**
     * Attributes used for comparing terminal to rule
     */
    const COMPARISON_ATTRIBUTES = [
        self::GATEWAY,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::NETWORK_CATEGORY,
        self::TERMINAL_TYPE,
        self::EMI_DURATION,
        self::CURRENCY,
    ];

    /**
     * Attributes for which the value can be null, signifying any/all values
     * are acceptable for comparison
     */
    const NULLABLE_ATTRIBUTES = [
        self::GROUP,
        self::FILTER_TYPE,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::MAX_AMOUNT,
        self::GATEWAY_ACQUIRER,
        self::NETWORK_CATEGORY,
        self::CATEGORY2,
        self::TERMINAL_TYPE,
        self::INTERNATIONAL,
        self::IINS,
        self::EMI_DURATION,
        self::CURRENCY,
    ];

    protected $entity = 'gateway_rule';

    protected $generateIdOnCreate = true;

    protected $casts = [
        self::INTERNATIONAL => 'boolean',
        self::LOAD          => 'int',
        self::MIN_AMOUNT    => 'int',
        self::MAX_AMOUNT    => 'int',
        self::IINS          => 'array',
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::GATEWAY,
        self::TYPE,
        self::GROUP,
        self::FILTER_TYPE,
        self::LOAD,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::NETWORK_CATEGORY,
        self::TERMINAL_TYPE,
        self::CATEGORY2,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::IINS,
        self::EMI_DURATION,
        self::CURRENCY,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::TYPE,
        self::GROUP,
        self::FILTER_TYPE,
        self::LOAD,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::NETWORK_CATEGORY,
        self::TERMINAL_TYPE,
        self::CATEGORY2,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::IINS,
        self::EMI_DURATION,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected static $modifiers = [
        self::NETWORK,
        self::ISSUER,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::LOAD
    ];

    protected $defaults = [
        self::MIN_AMOUNT => 0,
    ];

    public function getLoad()
    {
        return $this->getAttribute(self::LOAD);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getMethodType()
    {
        return $this->getAttribute(self::METHOD_TYPE);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getGatewayAcquirer()
    {
        return $this->getAttribute(self::GATEWAY_ACQUIRER);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function isTypeFilter(): bool
    {
        return ($this->getAttribute(self::TYPE) === self::FILTER);
    }

    public function isTypeSorter(): bool
    {
        return ($this->getAttribute(self::TYPE) === self::SORTER);
    }

    public function shouldSelectTerminal(): bool
    {
        return ($this->getAttribute(self::FILTER_TYPE) === self::SELECT);
    }

    public function shouldRejectTerminal(): bool
    {
        return ($this->getAttribute(self::FILTER_TYPE) === self::REJECT);
    }

    public function isMethodCardOrEmi(): bool
    {
        return (in_array($this->getMethod(), [Method::CARD, Method::EMI], true) === true);
    }

    public function getIins()
    {
        return $this->getAttribute(self::IINS);
    }

    public function getEmiDuration()
    {
        return $this->getAttribute(self::EMI_DURATION);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    //----------------- Public Setters------------------------------------------

    public function setPublicLoadAttribute(array & $array)
    {
        if (empty($this->getAttribute(self::LOAD)) === false)
        {
            $load = round(($this->getAttribute(self::LOAD) / 100), 2);

            $array[self::LOAD] = $load;
        }
    }

    //---------------- Public Setters End---------------------------------------

    //----------------------------Modifiers-------------------------------------

    protected function modifyNetwork(array & $input)
    {
        if (empty($input[self::NETWORK]) === false)
        {
            $input[self::NETWORK] = strtoupper($input[self::NETWORK]);
        }
    }

    protected function modifyIssuer(array & $input)
    {
        if (empty($input[self::ISSUER]) === false)
        {
            $input[self::ISSUER] = strtoupper($input[self::ISSUER]);
        }
    }

    //----------------------------Modifiers End---------------------------------

    //---------------- Mutators-------------------------------------------------

    public function setLoadAttribute($load)
    {
        if (empty($load) === false)
        {
            $this->attributes[self::LOAD] = intval(round($load * 100));
        }
    }

    //----------------- Mutators End--------------------------------------------


    public function getIinsAttribute($value)
    {
        if (empty($value) === true)
        {
            return [];
        }

        return json_decode($value, true);
    }

    /**
     * Evaluates if a rule's terminal related attributes match those of
     * given terminal
     *
     * @param  Terminal\Entity $terminal Terminal entity to compare against
     * @return bool whether rule matches terminal
     */
    public function matches(Terminal\Entity $terminal): bool
    {
        foreach (self::COMPARISON_ATTRIBUTES as $key)
        {
            // For certain attributes like gateway_acquirer, null means all, hence
            // we don't match if the value for these attributes is null
            if ((in_array($key, self::NULLABLE_ATTRIBUTES, true) === true) and
                ($this->isAttributeNull($key) === true))
            {
                continue;
            }

            if ($this->match($key, $terminal) === false)
            {
                return false;
            }
        }

        return true;
    }

    protected function match(string $key, Terminal\Entity $terminal): bool
    {
        return ($this->getAttribute($key) === $terminal->getAttribute($key));
    }
}
