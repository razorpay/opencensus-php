<?php

namespace Models\Pricing;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID                    = 'id';
    const PLAN_ID               = 'plan_id';
    const PLAN_NAME             = 'plan_name';
    const GATEWAY               = 'gateway';
    const PAYMENT_METHOD        = 'payment_method';
    const PAYMENT_METHOD_TYPE   = 'payment_method_type';
    const PAYMENT_NETWORK       = 'payment_network';
    const PAYMENT_ISSUER        = 'payment_issuer';
    const PERCENT_RATE          = 'percent_rate';
    const FIXED_RATE            = 'fixed_rate';
    const EXPIRED_AT            = 'expired_at';

    protected $fillable = array(
        self::ID,
        self::PLAN_ID,
        self::PLAN_NAME,
        self::GATEWAY,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::PAYMENT_NETWORK,
        self::PAYMENT_ISSUER,
        self::PERCENT_RATE,
        self::FIXED_RATE);

    protected $table = \Constants\Table::PRICING;

    protected $genereateIdOnCreate = true;

    /**
     * Fields which will be modified before
     * input validation
     *
     * @var array
     */
    protected static $modifiers = array('inputRemoveBlanks', 'inputProvideDefaults');

    protected static $generators = array('plan_id', 'rates');

    protected function modifyInputProvideDefaults(& $input)
    {
        $nullables = array(self::PAYMENT_METHOD_TYPE, self::PAYMENT_NETWORK, self::PAYMENT_ISSUER);

        foreach ($nullables as $key)
        {
            if (isset($input[$key]) === false)
            {
                $input[$key] = null;
            }
        }
    }

    protected function generateRates($input)
    {
        //
        // Sets the rates at 0 if not provided via input
        // The validator should check for the case where
        // both the rates aren't set
        //
        if (isset($input[self::PERCENT_RATE]) === false)
        {
            $this->setAttribute(self::PERCENT_RATE, 0);
        }

        if (isset($input[self::FIXED_RATE]) === false)
        {
            $this->setAttribute(self::FIXED_RATE, 0);
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

        $this->fill($input);

        $this->fillRule($input, $plan);

        return $this;
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

    public function fillRule($input, $plan)
    {
        $rule = $plan->first();

        $input[self::PLAN_ID] = $rule->getAttribute(self::PLAN_ID);
        $input[self::PLAN_NAME] = $rule->getAttribute(self::PLAN_NAME);
        $input[self::GATEWAY] = $rule->getAttribute(self::GATEWAY);

        return $this->fill($input);
    }

    public function getPercentRateAttribute()
    {
        return (int) $this->attributes[self::PERCENT_RATE];
    }

    public function getFixedRateAttribute()
    {
        return (int) $this->attributes[self::FIXED_RATE];
    }
}
