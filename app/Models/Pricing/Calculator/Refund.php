<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Exception;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Base as BaseModel;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

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
            // And this default pricing only applies to RZP Org merchants.
            // Instant Refunds is restricted to only these merchants.
            // This check has been kept at Payment/Processor/Refund.php : isInvalidInstantRefundsRequest()
            // https://github.com/razorpay/api/blob/faa1c6291b8e594d86785da15ad552cd8c2f9833/app/Models/Payment/Processor/Refund.php#L128
            //
            if (($rule === null) and
                ($this->entity->merchant->getOrgId() === Org\Entity::RAZORPAY_ORG_ID))
            {
                $rules = (new Pricing\Fee)->getInstantRefundsDefaultPricingPlanForMethod($this->entity);

                $this->traceRefundRules($rules, 'default_pricing_plan');

                $rules = $this->applyRefundModeFilters($rules);

                $this->traceRefundRules($rules, 'refund_mode_filtered');

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

    protected function applyAmountRangeFilterAndReturnOneRule($rules)
    {
        $payment = $this->entity;

        $amount = $this->amount;

        $filters = [
            [Pricing\Entity::AMOUNT_RANGE_ACTIVE, true, true, false]
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        $this->traceRefundRules($rules, 'amount_range_active_filtered');

        if (count($rules) === 0)
        {
            throw new Exception\LogicException(
                'Invalid rule count: 0, Merchant Id: ' . $payment->getMerchantId(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                [
                    'payment_id' => $payment->getId(),
                    'method'     => $payment->getMethod(),
                ]);
        }

        $rule = $this->chooseRuleWithAmount($rules, $amount);

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'Failed to find a valid pricing rule for the payment, Merchant Id: ' . $payment->getMerchantId(),
                ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
                [
                    'payment_id' => $payment->getId(),
                    'method'     => $payment->getMethod(),
                ]);
        }

        return $rule;
    }

    protected function traceRefundRules($rules, $message)
    {
        $merchantId = $this->entity->merchant->getId();

        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::LOG_REFUND_PRICING_RULES,
            $this->mode
        );

        if ($variant !== RefundConstants::RAZORX_VARIANT_ON)
        {
            return;
        }

        // This is sending a lot of traces and so for
        // this tracing is not required.
        $array = [];

        foreach ($rules as $rule)
        {
            $array[] = $rule->toArray();
        }

        $this->trace->info(
            TraceCode::PRICING_RULE_SELECTION,
            [
                'refund_id'   => $this->entity->getId(),
                'merchant_id' => $merchantId,
                'rules'       => $array,
                'message'     => $message,
            ]);
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
