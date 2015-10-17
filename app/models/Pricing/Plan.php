<?php

namespace Models\Pricing;

use Models\Base\PublicCollection;

class Plan extends PublicCollection
{
    const ID    = 'id';
    const RULES = 'rules';
    const NAME  = 'name';

    protected $entity = 'pricing';

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

    protected function getDefaultPlanCollectionValues()
    {
        return array(
            self::COUNT => 0,
            'entity' => 'collection',
            'items' => array());
    }

    /**
     * Returns an array containing multiple plans
     * Has the normal attributes 'entity', 'collection',
     * 'count' etc. with pricing plans and thie rrules
     * The function assumes that the plan rules in the
     * collection are already sorted descending by
     * plan_id and id. Actually, this should be ensured
     * when fetching data from repository
     *
     * @return array collection of multiple plans
     */
    public function toArrayMultiplePlansPublic()
    {
        $plans = $this->getDefaultPlanCollectionValues();

        if ($this->count() === 0)
        {
            return $plans;
        }

        $data = & $plans['items'];

        $first = true;
        $plan = array(self::ID => null);
        $rules = null;

        //
        // $this->items contain the pricing rules.
        // We assume that rules are sorted by plan id.
        // Now, we create a plan collection by pushing the plan rules inside
        // plan array.
        // The collection of plans array is multiple plans.
        //

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
                }
                else
                {
                    array_push($data, $plan);
                    $plans[self::COUNT]++;
                }

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
        $plan = array(
            self::ID        => $item->getPlanId(),
            self::NAME      => $item->getPlanName(),
            self::ENTITY    => $this->entity,
            self::COUNT     => $count,
            self::RULES     => $rules);

        return $plan;
    }
}