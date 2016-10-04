<?php

namespace RZP\Models\Pricing;

use RZP\Exception\LogicException;
use RZP\Models\Bank;
use RZP\Models\Payment\Method;
use RZP\Models\Card\Network;
use RZP\Models\Base\PublicCollection;

class Plan extends PublicCollection
{
    const ID    = 'id';
    const RULES = 'rules';
    const NAME  = 'name';

    protected $entity = 'pricing';

    /**
     * Get the collection of items as a plain array.
     * @return array
     * @throws LogicException
     */
    public function toArrayPublic()
    {
        $plan = array();
        $rules = array();

        if ($this->count() === 0)
            return array();

        foreach ($this->items as $item)
        {
            $rule = $item->toArray();

            // We need to send the human version of the payment network name
            // as well, so DICL becomes Diners Club and
            // AMEX becomes American Express
            if ($rule[Entity::PAYMENT_NETWORK] !== null)
            {
                $network = $rule[Entity::PAYMENT_NETWORK];

                $method = $rule[Entity::PAYMENT_METHOD];

                switch ($method)
                {
                    case Method::CARD:
                        $rule[Entity::PAYMENT_NETWORK_NAME] = Network::getFullName($network);
                        break;

                    case Method::NETBANKING:
                        $rule[Entity::PAYMENT_NETWORK_NAME] = Bank\Name::getName($network);
                        break;

                    default:
                        throw new LogicException(
                            'Network set for wrong method (not card/netbanking)',
                            null,
                            ['network' => $network, 'method' => $method]);
                }
            }

            array_push($rules, $rule);
        }

        $this->setPlanAttributes(
            $plan,
            $this->items[0],
            $rules,
            count($this->items));

        return $plan;
    }

    /**
     * Returns a string version of the rule's
     * Pricing
     * @param  array  $rule array containing the PERCENT_RATE
     * and the FIXED_RATE
     * @return string String representation of the rates
     */
    public static function formattedPricing(array $rule)
    {
        $res = "";
        $percent = false;

        if ($rule[Entity::PERCENT_RATE] !== 0)
        {
            $res .= $rule[Entity::PERCENT_RATE]/100 . "% TDR";
            $percent = true;
        }

        if ($rule[Entity::FIXED_RATE] !== 0)
        {
            if ($percent === true)
            {
                $res .= " + ";
            }
            $res .= "INR " . $rule[Entity::FIXED_RATE]/100 . " Fixed Charge";
        }

        return $res;
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
     * 'count' etc. with pricing plans and their rules
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

    public function hasMethod($method)
    {
        foreach ($this->items as $rule)
        {
            if ($rule->getPaymentMethod() === $method)
            {
                return true;
            }
        }

        return false;
    }

    public function hasNetworkAmex()
    {
        foreach ($this->items as $rule)
        {
            if ($rule->getPaymentNetwork() === 'AMEX')
            {
                return true;
            }
        }

        return false;
    }

    public function hasInternationalPricing()
    {
        foreach ($this->items as $rule)
        {
            if ($rule->isInternational())
            {
                return true;
            }
        }
        return false;
    }
}
