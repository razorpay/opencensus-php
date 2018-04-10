<?php

namespace RZP\Models\Gateway\Rule;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;

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

    const COMMENTS         = 'comments';
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
     * Attributes which define search criteria for both sorter / filter rules
     */
    const DEFAULT_SEARCH_ATTRIBUTES = [
        self::ID,
        self::MERCHANT_ID,
        self::TYPE,
        self::GROUP,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::CURRENCY,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
        self::INTERNATIONAL,
    ];

    /**
     * Attributes which define search criteria for filter rules.
     */
    const FILTER_SEARCH_ATTRIBUTES = [
        self::GATEWAY,
        self::FILTER_TYPE,
        self::SHARED_TERMINAL,
        self::NETWORK_CATEGORY,
        self::GATEWAY_ACQUIRER,
        self::CATEGORY2,
    ];

    /**
     * Defines the attribute scores used for calculating
     * specificity score for a rule. Each attribute is given
     * a score in power of 2 and two attributes cant have the same score.
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
        self::COMMENTS,
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
        self::COMMENTS,
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

    // ---------------------Overridden--------------------------

    /**
     * We are using custom collection class for this entity with some custom methods
     *
     * @param  array  $models models which are part of the collection
     *
     * @return Collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

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

    //---------------------------Accessors--------------------------------------
    public function getIinsAttribute($value)
    {
        if (empty($value) === true)
        {
            return [];
        }

        return json_decode($value, true);
    }
    //-------------------------Accessors End------------------------------------

    /**
     * From the list of all attributes of the rule entity, gets the list of
     * attributes which should be used for searching for matching rules
     *
     * @return array
     */
    public function getSearchCriteria(): array
    {
        $searchAttributes = $this->getSearchAttributes();

        $searchCriteria = array_filter($this->attributes, function ($value, $key) use ($searchAttributes)
        {
            return ((in_array($key, $searchAttributes, true) === true) and
                    ($value !== null));
        }, ARRAY_FILTER_USE_BOTH);

        return $searchCriteria;
    }

    /**
     * Gets the relevant search attributes depending on the type of the rule
     *
     * @return array
     */
    protected function getSearchAttributes(): array
    {
        $key = __CLASS__ . '::' . strtoupper($this->getType()) . '_SEARCH_ATTRIBUTES';

        $searchAttributesForType = [];

        if (defined($key) === true)
        {
            $searchAttributesForType = constant($key);
        }

        return array_merge(self::DEFAULT_SEARCH_ATTRIBUTES, $searchAttributesForType);
    }

    /**
     * Computes the specificity score for a rule, by adding up the scores of all the
     * non null attributes of the entity which are also present in the ATTRIBUTE_SCORES
     * array.
     *
     * For e.g a rule for VISA card merchant m1 will have score computed as follows
     * 8 (network = VISA) + 256 (merchant_id = M1) = 264
     *
     * @return int      specificity score of the rule
     */
    public function calculateSpecificityScore(): int
    {
        $totalScore = 0;

        foreach (self::ATTRIBUTE_SCORES as $attr => $score)
        {
            //
            // For certain attributes (iins, min / max amount) we need to do some
            // special handling to get the score. In such cases we call the special
            // method if defined.
            //
            $func = 'getScoreFor' . studly_case($attr);

            if (method_exists($this, $func) === true)
            {
                $totalScore += $this->$func();
            }
            //
            // If the attribute value is not null, we add up the score of that
            // attribute to the total score.
            //
            else if ($this->isAttributeNotNull($attr) === true)
            {
                $totalScore += $score;
            }
        }

        return $totalScore;
    }

    /**
     * Evaluates if a rule's terminal related attributes match those of
     * given terminal
     *
     * @param  Terminal\Entity $terminal Terminal entity to compare against
     * @param  Merchant\Entity $merchant
     *
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

            case Method::EMANDATE:
                return ($terminal->isEmandateEnabled() === true);
        }

        return false;
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

    /**
     * We only add the score for merchant_id if rule does not belong to the shared
     * merchant
     *
     * @return int
     */
    protected function getScoreForMerchantId(): int
    {
        if ($this->getMerchantId() !== Account::SHARED_ACCOUNT)
        {
            return self::ATTRIBUTE_SCORES[self::MERCHANT_ID];
        }

        return 0;
    }

    /**
     * Since iins is stored as an array, we only add the score if it is not empty
     *
     * @return int
     */
    protected function getScoreForIins(): int
    {
        if (empty($this->getIins()) === false)
        {
            return self::ATTRIBUTE_SCORES[self::IINS];
        }

        return 0;
    }

    /**
     * We add the score for amount_range if either min_amount or max_amount has
     * a non-empty value
     *
     * @return int
     */
    protected function getScoreForAmountRange(): int
    {
        if ((empty($this->getAttribute(self::MIN_AMOUNT)) === false) or
            (empty($this->getAttribute(self::MAX_AMOUNT)) === false))
        {
            return self::ATTRIBUTE_SCORES[self::AMOUNT_RANGE];
        }

        return 0;
    }
}
