<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Exception\LogicException;
use RZP\Models\Base as BaseModel;
use RZP\Models\Pricing;
use RZP\Models\Admin\Org;
use RZP\Models\Pricing\Fee;
use RZP\Constants;
use RZP\Constants\Metric as MetricConstants;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount\Validation\Constants as FavConstants;
use RZP\Trace\TraceCode;

class FundAccountValidation extends Base
{
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);
    }

    protected function getBasicPricingRule(Pricing\Plan $pricing, $feature)
    {
        $method   = $this->entity->getMethod();

        $product  = $this->product;

        $filters = $this->getBasicPricingRuleFilters($product, $feature, $method);

        $rules = $this->applyFiltersOnRules($pricing, $filters);

        $rule = $this->getPricingRule($rules, $method);

        $this->trace->info(TraceCode::FAV_PRICING_RULE_SELECTED,
            [
                'rule' => $rule->toArray(),
                'entity_id' => $this->entity->getId(),
                'merchant_id' => $this->entity->getMerchantId(),
            ]);

        $this->pricingRules->push($rule);
    }

    /**
     * @throws LogicException
     */
    protected function getPricingRule($rules, $method)
    {
        // Apply validation method filters for fund account validation
        $rules = $this->applyValidationMethodFilters($rules);

        $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);

        return $rule;
    }

    /**
     * Apply validation method filters for fund account validation
     * This filters pricing rules based on the validation method type
     * (pennydrop, penniless, optimized, etc.)
     *
     * @param array $rules
     * @return array
     * @throws LogicException
     */
    protected function applyValidationMethodFilters(array $rules): array
    {
        $validationMethod = $this->getValidationMethod();

        if (empty($validationMethod) === true)
        {
            // If no validation method is set, return rules as-is
            return $rules;
        }

        // Apply filter based on validation method
        // The validation method will be stored in payment_method_type field
        // as per the proposed approach
        $filters = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE, $validationMethod, true, null],
        ];

        $filteredRules = $this->applyFiltersOnRules($rules, $filters);

        // If no rules match the specific validation method,
        // fall back to default pricing rules (without validation method filter)
        if (empty($filteredRules) === true)
        {
            $this->trace->count(MetricConstants::SERVER_ERROR_NO_PRICING_RULE_FOUND ,
                [
                    'route_name' => $this->app['api.route']->getCurrentRouteName(),
                    'entity' => "fund_account_validation",
                    'method' => $validationMethod,
                ]);

            throw new Exception\LogicException(
                'No appropriate pricing rule found for entity ' .
                $this->entity->getEntity(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                [
                    'entity' => $this->entity->getId(),
                    'validation_method' => $validationMethod,
                ]);
        }

        return $filteredRules;
    }

    /**
     * Get validation method from the entity
     * Maps the validation method to the format expected by pricing rules
     *
     * @return string
     */
    protected function getValidationMethod(): string
    {
        // Get validation method from entity attribute
        $validationMethod = $this->entity->getAttribute('validation_type');

        if (empty($validationMethod) === true)
        {
            return "";
        }

        // Map validation method to payment_method_type values
        // as per the proposed approach
        switch ($validationMethod)
        {
            case FavConstants::TYPE_PENNYDROP:
                return FavConstants::TYPE_PENNYDROP;

            case FavConstants::TYPE_CITI_PENNILESS:
            case FavConstants::TYPE_PENNILESS:
                return FavConstants::TYPE_PENNILESS;

            default:
                return $validationMethod;
        }
    }

    public function validateFees($totalFees)
    {
        // Can't use fee credits for fund account validation, so only balance matters
        return;
    }

    protected function isFeeBearerCustomer()
    {
        // Customer fee bearer is not supported for Fund account validation
        return false;
    }
}
