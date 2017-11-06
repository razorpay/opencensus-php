<?php

namespace RZP\Models\Gateway\Rule;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant;
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
    const SHARED_TERMINAL  = 'shared_terminal';

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
    const EMI_SUBVENTION   = 'emi_subvention';
    const INTERNATIONAL    = 'international';
    const CURRENCY         = 'currency';

    // Merchant properties
    const CATEGORY2        = 'category2';

    const DELETED_AT       = 'deleted_at';

    //
    // Constant denotes the range defined by
    // min_amount and max_amount for a rule if
    // either or both are present
    //
    const AMOUNT_RANGE = 'amount_range';

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
        self::METHOD,
        self::GATEWAY,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::NETWORK_CATEGORY,
        self::SHARED_TERMINAL,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
        self::CURRENCY,
    ];

    /**
     * Attributes for which the value can be null, signifying any/all values
     * are acceptable for comparison
     */
    const NULLABLE_ATTRIBUTES = [
        self::GROUP,
        self::FILTER_TYPE,
        self::GATEWAY,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::MAX_AMOUNT,
        self::GATEWAY_ACQUIRER,
        self::NETWORK_CATEGORY,
        self::CATEGORY2,
        self::SHARED_TERMINAL,
        self::INTERNATIONAL,
        self::EMI_DURATION,
        self::IINS,
        self::CURRENCY,
    ];

    /**
     * Defines the attribute scores used for calculating how specific a rule
     * is for a given criteria. Each attribute is given a score in power of 2
     * and two attributes cant have the same score.
     */
    const ATTRIBUTE_SCORES = [
        self::CURRENCY      => 1,
        self::INTERNATIONAL => 2,
        self::METHOD_TYPE   => 4,
        self::NETWORK       => 8,
        self::ISSUER        => 16,
        self::IINS          => 32,
        self::AMOUNT_RANGE  => 64,
        self::CATEGORY2     => 128,
        self::MERCHANT_ID   => 256,
    ];

    protected $entity = 'gateway_rule';

    protected $generateIdOnCreate = true;

    protected $casts = [
        self::INTERNATIONAL   => 'boolean',
        self::SHARED_TERMINAL => 'boolean',
        self::LOAD            => 'int',
        self::MIN_AMOUNT      => 'int',
        self::MAX_AMOUNT      => 'int',
        self::EMI_DURATION    => 'int',
        self::IINS            => 'array',
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
        self::SHARED_TERMINAL,
        self::CATEGORY2,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::IINS,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
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
        self::SHARED_TERMINAL,
        self::CATEGORY2,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::IINS,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
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
        self::LOAD,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
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

    public function getGroup()
    {
        return $this->getAttribute(self::GROUP);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getFilterType()
    {
        return $this->getAttribute(self::FILTER_TYPE);
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

    public function isFilter(): bool
    {
        return ($this->getAttribute(self::TYPE) === self::FILTER);
    }

    public function isSorter(): bool
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

    public function getEmiSubvention()
    {
        return $this->getAttribute(self::EMI_SUBVENTION);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }
    //----------------- Public Setters------------------------------------------

    public function setPublicLoadAttribute(array & $array)
    {
        if ($this->getAttribute(self::LOAD) !== null)
        {
            $load = round(($this->getAttribute(self::LOAD) / 100), 2);

            $array[self::LOAD] = $load;
        }
    }

    public function setPublicMinAmountAttribute(array & $array)
    {
        if ($this->getAttribute(self::MIN_AMOUNT) !== null)
        {
            $minAmount = round(($this->getAttribute(self::MIN_AMOUNT) / 100), 2);

            $array[self::MIN_AMOUNT] = $minAmount;
        }
    }

    public function setPublicMaxAmountAttribute(array & $array)
    {
        if ($this->getAttribute(self::MAX_AMOUNT) !== null)
        {
            $maxAmount = round(($this->getAttribute(self::MAX_AMOUNT) / 100), 2);

            $array[self::MAX_AMOUNT] = $maxAmount;
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
        if ((empty($input[self::ISSUER]) === false) and
            ($input[self::METHOD] !== Method::WALLET))
        {
            $input[self::ISSUER] = strtoupper($input[self::ISSUER]);
        }
    }

    //----------------------------Modifiers End---------------------------------

    //---------------- Mutators-------------------------------------------------

    public function setLoadAttribute($load)
    {
        if ($load !== null)
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

    public function calculateSpecificityScoreForCriteria(array $criteria)
    {
        $score = 0;
        foreach ($criteria as $attr => $value)
        {
            if (($this->isAttributeNotNull($attr) === true) and
                ($value === $this->getAttribute($attr)))
            {
                $score += self::ATTRIBUTE_SCORES[$attr];
            }
        }

        sd($score);
    }

    /**
     * Evaluates if a rule's terminal related attributes match those of
     * given terminal
     *
     * @param  Terminal\Entity $terminal Terminal entity to compare against
     * @return bool whether rule matches terminal
     */
    public function matches(Terminal\Entity $terminal, Merchant\Entity $merchant): bool
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

            if ($this->compare($key, $terminal, $merchant) === false)
            {
                return false;
            }
        }

        return true;
    }

    protected function compare(string $key, Terminal\Entity $terminal, Merchant\Entity $merchant): bool
    {
        $compareFunc = 'compare' . studly_case($key);

        if (method_exists($this, $compareFunc) === true)
        {
            return $this->$compareFunc($terminal, $merchant);
        }

        return ($this->getAttribute($key) === $terminal->getAttribute($key));
    }

    protected function compareMethod(Terminal\Entity $terminal): bool
    {
        $method = $this->getMethod();

        switch ($method)
        {
            case Method::CARD:
                return (($terminal->isCardEnabled() === true) and ($terminal->isEmiEnabled() === false));

            case Method::NETBANKING:
                return ($terminal->isNetbankingEnabled() === true);

            case Method::EMI:
                return ($terminal->isEmiEnabled() === true);

            case Method::WALLET:
                return ($this->getGateway() === $terminal->getGateway());

            case Method::UPI:
                return ($terminal->isUpiEnabled() === true);

            case Method::AEPS:
                return ($terminal->isAepsEnabled() === true);
        }
    }

    /**
     * Compares international property of rue with terminal.
     * If international is true then terminal should have international enabled
     * If international is false then terminal should have card enabled
     *
     * @param  Terminal\Entity $terminal Terminal to check against
     * @return bool                      Comparison result
     */
    protected function compareInternational(Terminal\Entity $terminal): bool
    {
        return ($this->isInternational() === true) ?
                $terminal->isInternational() :
                $terminal->isDomestic();
    }

    /**
     * Checks if a terminal is shared / direct against against what the rule
     * specifies. The cases for the same are listed below
     * - Shared terminal, with a submerchant assigned as given merchant
     * - Terminal directly assigned to merchant
     * - Terminal assigned to some other merchant with given merchant as a submerchant
     *
     * @param  Terminal\Entity $terminal Terminal to check against
     * @param  Merchant\Entity $merchant Merchant making the payment
     * @return bool                      Comparison result
     */
    protected function compareSharedTerminal(Terminal\Entity $terminal, Merchant\Entity $merchant): bool
    {
        $isApplicableForSharedTerminal = $this->getAttribute(self::SHARED_TERMINAL);

        return ($isApplicableForSharedTerminal !== $terminal->isDirectForMerchant($merchant)) ? true : false;
    }
}
