<?php

namespace RZP\Models\Pricing;

use RZP\Models\Bank;
use RZP\Models\Base\Utility;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Exception\LogicException;
use RZP\Models\Payment\Processor;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\PaymentsUpi;

class Plan extends PublicCollection
{
    const ID     = 'id';
    const RULES  = 'rules';
    const NAME   = 'name';
    const ORG_ID = 'org_id';

    protected $entity = 'pricing';

    public function getType()
    {
        // one plan can contain rules of one type only
        if ($this->isNotEmpty() === true)
        {
            return $this->first()->getType();
        }

        // The pricing plan has no rules
        return null;
    }

    public function isTypePricing(): bool
    {
        return ($this->getType() === Type::PRICING);
    }

    public function isTypeCommission(): bool
    {
        return ($this->getType() === Type::COMMISSION);
    }

    /**
     * Extract and return the plan id from the public collection of pricing rules
     *
     * @return null
     */
    public function getId()
    {
        if ($this->isNotEmpty() === true)
        {
            return $this->first()->getPlanId();
        }

        return null;
    }

    public function getPlanName()
    {
        if ($this->isNotEmpty() === true)
        {
            return $this->first()->getPlanName();
        }

        return null;
    }

    /**
     * Get the collection of items as a plain array.
     * @return array
     * @throws LogicException
     */
    public function toArrayPublic()
    {
        $plan = $rules = [];

        if ($this->count() === 0)
        {
            return [];
        }

        /** @var Entity $item */
        foreach ($this->items as $item)
        {
            $rule = $item->toArray();

            //
            // We need to send the human version of the payment network name
            // as well, so DICL becomes Diners Club and
            // AMEX becomes American Express
            //
            if ($rule[Entity::PAYMENT_NETWORK] !== null)
            {
                $network = $rule[Entity::PAYMENT_NETWORK];

                $method = $rule[Entity::PAYMENT_METHOD];

                switch ($method)
                {
                    case Method::CARD:
                    case Method::EMI:
                        $rule[Entity::PAYMENT_NETWORK_NAME] = Network::getFullName($network);
                        break;

                    case Method::NETBANKING:
                    case Method::EMANDATE:
                        $rule[Entity::PAYMENT_NETWORK_NAME] = Bank\Name::getName($network);
                        break;

                    case Method::WALLET:
                        $rule[Entity::PAYMENT_NETWORK_NAME] = Processor\Wallet::getName($network);
                        break;
                    case Method::APP:
                        $rule[Entity::PAYMENT_NETWORK_NAME] = Processor\App::getName($network);
                        break;
                    case Method::INTL_BANK_TRANSFER:
                        $rule[Entity::PAYMENT_NETWORK_NAME] = Processor\IntlBankTransfer::getName($network);
                        break;

                    default:
                        break;
                }
            }

            array_push($rules, $rule);
        }

        $this->setPlanAttributes($plan, $this->items[0], $rules, count($this->items));

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

    public static function formattedBuyPricing(array $rule)
    {
        $formattedRules = [];

        $items = [];

        foreach (Entity::$buyPricingMethods as $method)
        {
            $attribute = $rule[$method] ?? '';

            if (is_array($attribute))
            {
                $items[$method] = $attribute;
            }
        }

        foreach (Utility::getCombinations($items) as $item)
        {
            $item[Entity::IS_BUY_PRICING_ALLOWED] = true;

            $item[Entity::AMOUNT_RANGE_ACTIVE] = true;

            $item[Entity::TYPE] = Type::BUY_PRICING;

            $formattedRules[] = array_merge($rule, $item);
        }

        return $formattedRules;
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

        /** @var Entity $item */
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
        /** @var Entity $item */
        $plan = array(
            self::ID        => $item->getPlanId(),
            self::NAME      => $item->getPlanName(),
            self::ENTITY    => $this->entity,
            self::ORG_ID    => $item->getOrgId(),
            self::COUNT     => $count,
            self::RULES     => $rules);

        return $plan;
    }

    public function hasQrCodeReceiver()
    {
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if ($rule->getReceiverType() === Receiver::QR_CODE)
            {
                return true;
            }
        }

        return false;
    }
    public function hasCreditReceiver()
    {
        foreach ($this->items as $rule)
        {
            if ($rule->getReceiverType() === PaymentsUpi\PayerAccountType::PRICING_PLAN_RECEIVER_TYPE_CREDIT)
            {
                return true;
            }
        }

        return false;
    }

    public function hasVpaReceiver()
    {
        foreach ($this->items as $rule)
        {
            if ($rule->getReceiverType() === Receiver::VPA)
            {
                return true;
            }
        }
        return false;
    }

    public function hasMethod($method)
    {
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if ($rule->getPaymentMethod() === $method)
            {
                return true;
            }
        }

        return false;
    }

    public function hasMethodForFeature($method, $feature, bool $skipFeatureCheck = true)
    {
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if ($rule->getPaymentMethod() === $method && ($skipFeatureCheck || $rule->getFeature() === $feature))
            {
                return true;
            }
        }

        return false;
    }

    public function hasNetworkAmex()
    {
        /** @var Entity $rule */
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
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if ($rule->isInternational())
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the org id of pricing plan.
     *
     * @return string|null
     */
    public function getOrgId()
    {
        if ($this->count() !== 0)
        {
            return $this->items[0]->getOrgId();
        }

        return null;
    }

    public function hasBankingSharedAccountNonFreePayoutRule(): bool
    {
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if (($rule->isBankingProduct() === true) and
                ($rule->getFeature() === Feature::PAYOUT) and
                ($rule->isAccountTypeShared() === true) and
                ($rule->isPayoutsFilterFreePayout() === false))
            {
                return true;
            }
        }

        return false;
    }

    public function hasBankingDirectAccountNonFreePayoutRule(): array
    {
        $rblRulePresent = false;

        $iciciRulePresent = false;

        $axisRulePresent = false;

        $yesbankRulePresent = false;

        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if (($rule->isBankingProduct() === true) and
                ($rule->getFeature() === Feature::PAYOUT) and
                ($rule->isAccountTypeDirect() === true) and
                ($rule->isPayoutsFilterFreePayout() === false))
            {
                $channel = $rule->getChannel();

                if ($channel !== null)
                {
                    ${$channel . 'RulePresent'} = true;
                }
            }
        }

        return [$rblRulePresent, $iciciRulePresent, $axisRulePresent, $yesbankRulePresent];
    }

    public function hasBankingSharedAccountFreePayoutRule(): bool
    {
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if (($rule->isBankingProduct() === true) and
                ($rule->getFeature() === Feature::PAYOUT) and
                ($rule->isAccountTypeShared() === true) and
                ($rule->isPayoutsFilterFreePayout() === true))
            {
                return true;
            }
        }

        return false;
    }

    public function hasBankingDirectAccountFreePayoutRule(): bool
    {
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if (($rule->isBankingProduct() === true) and
                ($rule->getFeature() === Feature::PAYOUT) and
                ($rule->isAccountTypeDirect() === true) and
                ($rule->isPayoutsFilterFreePayout() === true))
            {
                return true;
            }
        }

        return false;
    }

    public function hasAppPayoutPricingRule(): bool
    {
        /** @var Entity $rule */
        foreach ($this->items as $rule)
        {
            if (($rule->isBankingProduct() === true) and
                ($rule->getFeature() === Feature::PAYOUT) and
                ($rule->isAppPayoutPricingRule() === true))
            {
                return true;
            }
        }

        return false;
    }
}
