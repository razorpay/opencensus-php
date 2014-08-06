<?php

namespace Models\Pricing;

class Plan extends \Illuminate\Database\Eloquent\Collection
{
    const ID = 'id';
    const COUNT = 'count';
    const RULES = 'rules';
    const NAME = 'name';

    const ENTITY = 'pricing_plan';

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArrayPublic()
    {
        $plan = array();
        $rules = array();

        if ($this->count() === 0)
            return array();

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

    public function toArrayMultiplePlansPublic()
    {
        $this->sortBy('plan_id');

        $plans = array();
        $plans[self::COUNT] = 0;
        $plans['entity'] = 'collection';
        $plans['data'] = array();
        $data = & $plans['data'];

        $first = true;
        $plan = array(self::ID => null);
        $rules = null;

        foreach ($this->items as $item)
        {
            if ($plan[self::ID] === $item->getPlanId())
            {
                $plan[self::COUNT]++;
                array_push($rules, $item->toArray());
            }
            else
            {
                if ($first === true)
                {
                    $first = false;
                    $this->setPlanAttributes($plan, $item);
                    $plan[self::COUNT] = 1;
                    $rules = & $plan[self::RULES];
                    array_push($rules, $item->toArray());
                    continue;
                }

                array_push($data, $plan);
                $plans[self::COUNT]++;

                $plan = array();
                $this->setPlanAttributes($plan, $item);
                $plan[self::COUNT] = 1;
                $rules = & $plan[self::RULES];

                array_push($rules, $item->toArray());
            }
        }

        array_push($data, $plan);
        $plans[self::COUNT]++;

        return $plans;
    }

    protected function setPlanAttributes(& $plan, $item, $rules = array(), $count = 0)
    {
        $plan[self::ID] = $item->getPlanId();
        $plan[self::NAME] = $item->getPlanName();
        $plan['entity'] = self::ENTITY;
        $plan[self::COUNT] = $count;
        $plan[self::RULES] = $rules;
    }
}