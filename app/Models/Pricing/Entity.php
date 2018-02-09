<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                   = 'id';
    const PLAN_ID              = 'plan_id';
    const PLAN_NAME            = 'plan_name';
    const FEATURE              = 'feature';
    const GATEWAY              = 'gateway';
    const PAYMENT_METHOD       = 'payment_method';
    const PAYMENT_METHOD_TYPE  = 'payment_method_type';
    const PAYMENT_NETWORK      = 'payment_network';
    const INTERNATIONAL        = 'international';

    // Humanized name of the payment network
    const PAYMENT_NETWORK_NAME = 'payment_network_name';
    const PAYMENT_ISSUER       = 'payment_issuer';

    const EMI_DURATION         = 'emi_duration';

    // Amount Range Rule
    const AMOUNT_RANGE_ACTIVE  = 'amount_range_active';
    const AMOUNT_RANGE_MIN     = 'amount_range_min';
    const AMOUNT_RANGE_MAX     = 'amount_range_max';

    const PERCENT_RATE         = 'percent_rate';
    const FIXED_RATE           = 'fixed_rate';

    // Min And Max Rate
    const MIN_FEE              = 'min_fee';
    const MAX_FEE              = 'max_fee';

    const EXPIRED_AT           = 'expired_at';
    const DELETED_AT           = 'deleted_at';

    // Input key for array of rules
    const RULES                = 'rules';

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::ID,
        self::PLAN_ID,
        self::PLAN_NAME,
        self::FEATURE,
        self::GATEWAY,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::PAYMENT_NETWORK,
        self::PAYMENT_ISSUER,
        self::INTERNATIONAL,
        self::AMOUNT_RANGE_ACTIVE,
        self::AMOUNT_RANGE_MIN,
        self::AMOUNT_RANGE_MAX,
        self::PERCENT_RATE,
        self::FIXED_RATE,
        self::MIN_FEE,
        self::MAX_FEE,
        self::EMI_DURATION,
    ];

    protected $entity = 'pricing';

    // We are explicitly generating Id so that same Id gets stored in live and test db
    protected $generateIdOnCreate = false;

    /**
     * Fields which will be modified before
     * input validation
     *
     * @var array
     */
    protected static $modifiers = ['inputRemoveBlanks', 'inputProvideDefaults'];

    protected static $generators = ['plan_id'];

    protected $defaults = [
        self::FEATURE             => Feature::PAYMENT,
        self::PAYMENT_METHOD_TYPE => null,
        self::PAYMENT_NETWORK     => null,
        self::PAYMENT_ISSUER      => null,
        self::PERCENT_RATE        => 0,
        self::FIXED_RATE          => 0,
        self::MIN_FEE             => 0,
        self::MAX_FEE             => null,
        self::AMOUNT_RANGE_ACTIVE => '0',
        self::EMI_DURATION        => null,
    ];

    /**
     * Adds casts for fields
     *
     * @var array
     */
    protected $casts = [
        self::INTERNATIONAL       => 'bool',
        self::AMOUNT_RANGE_ACTIVE => 'bool',
        self::PERCENT_RATE        => 'int',
        self::FIXED_RATE          => 'int',
        self::MIN_FEE             => 'int',
        self::EMI_DURATION        => 'int',
    ];

    const ZERO_PRICING = '10ZeroPricingP';

    protected function modifyInputProvideDefaults(& $input)
    {
        foreach ($this->defaults as $key => $value)
        {
            if (empty($input[$key]))
            {
                $input[$key] = $value;
            }
        }

        if ($input[self::AMOUNT_RANGE_ACTIVE] !== '1')
        {
            $input[self::AMOUNT_RANGE_MIN] = null;
            $input[self::AMOUNT_RANGE_MAX] = null;
        }
    }

    public function build(array $input = array())
    {
        $this->modify($input);

        $this->getValidator()->createPlanValidate($input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    public function addPlanRule($input, Plan $plan)
    {
        $this->modify($input);

        $this->getValidator()->addPlanRuleValidate($input, $plan);

        $this->generate($input);

        $this->fill($input);

        $this->fillRule($input, $plan);

        return $this;
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function isAmountRangeActive()
    {
        return $this->getAttribute(self::AMOUNT_RANGE_ACTIVE);
    }

    public function payments()
    {
        return $this->hasMany('RZP\Models\Transaction\Entity', 'pricing_rule_id');
    }

    public function feesBreakup()
    {
        return $this->hasMany('RZP\Models\Transaction\FeeBreakup\Entity', 'pricing_rule_id');
    }


    protected function generatePlanId()
    {
        $this->setAttribute(self::PLAN_ID, static::generateUniqueId());
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Plan($models);
    }

    public function fillRule($input, $plan)
    {
        $rule = $plan->first();

        $input[self::PLAN_ID] = $rule->getAttribute(self::PLAN_ID);
        $input[self::PLAN_NAME] = $rule->getAttribute(self::PLAN_NAME);
        $input[self::GATEWAY] = $rule->getAttribute(self::GATEWAY);

        return $this->fill($input);
    }

    public function getRates()
    {
        return [$this->getPercentRate(), $this->getFixedRate()];
    }

    public function getMinMaxFees()
    {
        return [$this->getMinFee(), $this->getMaxFee()];
    }

    public function getPlanId()
    {
        return $this->getAttribute(self::PLAN_ID);
    }

    public function getPlanName()
    {
        return $this->getAttribute(self::PLAN_NAME);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getPaymentNetwork()
    {
        return $this->getAttribute(self::PAYMENT_NETWORK);
    }

    public function getPaymentMethod()
    {
        return $this->getAttribute(self::PAYMENT_METHOD);
    }

    public function getPaymentMethodType()
    {
        return $this->getAttribute(self::PAYMENT_METHOD_TYPE);
    }

    public function getAmountRange()
    {
        return [$this->getAmountRangeMin(), $this->getAmountRangeMax()];
    }

    public function getAmountRangeMin()
    {
        return $this->getAttribute(self::AMOUNT_RANGE_MIN);
    }

    public function getAmountRangeMax()
    {
        return $this->getAttribute(self::AMOUNT_RANGE_MAX);
    }

    public function getFixedRate()
    {
        return $this->getAttribute(self::FIXED_RATE);
    }

    public function getPercentRate()
    {
        return $this->getAttribute(self::PERCENT_RATE);
    }

    public function getMinFee()
    {
        return $this->getAttribute(self::MIN_FEE);
    }

    public function getMaxFee()
    {
        return $this->getAttribute(self::MAX_FEE);
    }

    public function getEmiDuration()
    {
        return $this->getAttribute(self::EMI_DURATION);
    }

    protected function getAmountRangeMinAttribute()
    {
        $min = $this->attributes[self::AMOUNT_RANGE_MIN];

        return ($min === null) ? $min : (int) $min;
    }

    protected function getAmountRangeMaxAttribute()
    {
        $max = $this->attributes[self::AMOUNT_RANGE_MAX];

        return ($max === null) ? $max : (int) $max;
    }

    protected function getMaxFeeAttribute()
    {
        $max = $this->attributes[self::MAX_FEE];

        return ($max === null) ? $max : (int) $max;
    }

    public function getFeature()
    {
        return $this->attributes[self::FEATURE];
    }

    /*
     * For adding plan id easily in queries
     */
    public function scopePlanId($query, $planId)
    {
        $query->where(self::PLAN_ID, '=', $planId);
    }
}
