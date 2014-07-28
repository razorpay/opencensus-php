<?php

namespace Models\Pricing;

class Plan extends \Illuminate\Database\Eloquent\Collection
{
    const ID = 'id';
    const COUNT = 'count';
    const RULES = 'rules';
    const NAME = 'name';
    const ENTITY = 'entity';

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArrayPublic()
    {
        $plan = array();
        $rules = array();

        foreach ($this->items as $item)
        {
            array_push($rules, $item->toArray());
        }

        $this->setPlanAttributes(
            $plan,
            $this->items[0],
            $rules,
            count($this->items));

        return $plan;
    }

    public function toArrayMultiplePlans()
    {
        $plans = array();
        $plans[self::COUNT] = 0;

        $plan = array();
        $this->setPlanAttributes($plan, $this->items[0]);
        $rules = & $plan[self::RULES];

        foreach ($this->items as $item)
        {
            if ($plan[self::ID] === $item->getPlanId())
            {
                $plan[self::COUNT]++;
                array_push($rules, $item->toArray());
            }
            else
            {
                array_push($plans, $plan);
                $plans[self::COUNT]++;

                $plan = array();
                $this->setPlanAttributes($plan, $item);
                $rules = & $plan[self::RULES];

                array_push($rules, $item->toArray());
            }
        }

        return $plans;
    }

    protected function setPlanAttributes(& $plan, $item, $rules = array(), $count = 0)
    {
        $plan[self::ID] = $item->getPlanId();
        $plan[self::NAME] = $item->getPlanName();
        $plan[self::ENTITY] = 'pricing_plan';
        $plan[self::COUNT] = $count;
        $plan[self::RULES] = $rules;
    }
}