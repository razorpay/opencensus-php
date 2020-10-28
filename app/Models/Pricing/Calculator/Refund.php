<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Exception;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Base as BaseModel;

/**
 * Class Refund
 *
 * @package RZP\Models\Pricing\Calculator
 *
 * @property \RZP\Models\Payment\Refund\Entity $entity
 */
class Refund extends Base
{
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);
    }

    protected function setAmount()
    {
        $this->amount = $this->entity->getBaseAmount();
    }

    protected function getPricingRule($rules, $method)
    {
        $rule = null;

        try
        {
            $rules = $this->applyRefundModeFilters($rules);

            $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);
        }
        catch (\Throwable $ex)
        {
            //
            // In refunds - specifically Instant Refunds we have defined a default pricing plan
            // If merchant specific rules are not found after filtering, we want to apply the default pricing plan
            // instead of failing refund creation.
            //
            // This is possible only with this approach because merchant may have some rules defined, not all.
            // In that scenario to cover all cases default pricing plan will be invoked only
            // if merchant rules are not enough
            //
            if ($rule === null)
            {
                $rules = (new Pricing\Fee)->getInstantRefundsDefaultPricingPlanForMethod($this->entity);

                $rules = $this->applyRefundModeFilters($rules);

                $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);
            }
        }

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'Invalid rule count: 0, Merchant Id: ' . $this->entity->getMerchantId(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                [
                    'refund_id' => $this->entity->getId(),
                    'method'    => $this->entity->getMethod(),
                ]);
        }

        return $rule;
    }

    public function validateFees($totalFees)
    {
        // For refund, we don't have to check for fees > amount, since
        // the balance check and balance deduction happens almost together.
        return;
    }

    protected function applyRefundModeFilters($rules)
    {
        $mode = $this->entity->getModeRequested();

        $filters = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE, $mode, true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    protected function getBasicPricingRuleFilters($product, $feature, $method) : array
    {
        // Allowing default method - null
        $filters = [
            [Pricing\Entity::PRODUCT,        $product,   false, null],
            [Pricing\Entity::FEATURE,        $feature,   false, null],
            [Pricing\Entity::PAYMENT_METHOD, $method,    true,  null],
        ];

        return $filters;
    }
}
