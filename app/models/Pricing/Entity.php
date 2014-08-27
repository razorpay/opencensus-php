<?php

namespace Models\Pricing;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID                    = 'id';
    const PLAN_ID               = 'plan_id';
    const PLAN_NAME             = 'plan_name';
    const GATEWAY               = 'gateway';
    const PAYMENT_MODE          = 'payment_mode';
    const PAYMENT_MODE_TYPE     = 'payment_mode_type';
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
        self::PAYMENT_MODE,
        self::PAYMENT_MODE_TYPE,
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
    protected static $modifiers = array('inputRemoveBlanks');

    protected function modifyInputRemoveBlank(& $input)
    {
        foreach ($input as $key => $value)
        {
            if ($input[$key] === '')
            {
                $input[$key] = null;
            }
        }
    }

    public function newPlan()
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
}
