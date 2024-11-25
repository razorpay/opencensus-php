<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Constants\Product;
use RZP\Exception\LogicException;
use RZP\Models\Base as BaseModel;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card;
use RZP\Models\Currency\Core;
use RZP\Models\Currency\Currency;
use RZP\Models\Pricing;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as QrV2Entity;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Pricing\Fee;
use RZP\Models\Order\ProductType;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Payment as PaymentModel;
use RZP\Constants\Entity ;

class PayAsYouGo extends Base
{
    const UNITS = 'units';
    const METHOD = 'method';
    const AMOUNT = 'amount';
    const FREQUENCY = 'frequency';

    // Affordability methods
    const WIDGET = 'widget';
    const ELIGIBILITY = 'eligibility';

    // TokenHQ methods
    const PAR_API = 'par_api';
    const GET_TOKEN = 'get_token';
    const FETCH_CRYPTOGRAM = 'fetch_cryptogram';

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_YEARLY = 'yearly';

    const AFFORDABILITY_METHOD_LIST = [
        self::WIDGET,
        self::ELIGIBILITY
    ];

    const TOKENHQ_METHOD_LIST = [
        self::PAR_API,
        self::GET_TOKEN,
        self::FETCH_CRYPTOGRAM
    ];
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);
    }

    protected function getPricingRule($rules, $method)
    {
        $rule = $this->applyAmountRangeFilterAndReturnOneRule($rules);

        return $rule;
    }

    protected function setAmount()
    {
    }

    /**
     *
     * Uses the following formula for fees calculation
     * rzpVASFees = unit_fees * no. of units + percent * amount
     *
     * @param int $fixed
     * @param $units
     * @param int $percent e.g 2% is 200
     * @param $amount
     * @return float|int
     */
    private function getUnroundedVASFees(int $fixed, $units, int $percent, $amount, $percentScaleFactor = 100): float|int
    {
        return ($fixed * $units) + (($amount * $percent) / (100 * $percentScaleFactor));
    }

    private function calculateRzpVASFee(Pricing\Entity $rule, int $units, $amount)
    {
        list($percent, $fixed) = $rule->getRates();

        $percentScaleFactor = $rule->getPercentRateScaleFactor();
        if (in_array($percentScaleFactor, self::VALID_PERCENT_RATE_SCALE_FACTOR_VALUES) === false) {
            $percentScaleFactor = self::DEFAULT_PERCENT_RATE_SCALE_FACTOR;
        }

        $fee = $this->getUnroundedVASFees($fixed, $units, $percent, $amount, $percentScaleFactor);

        $fee = (int) ceil($fee);

        $rzpFee = $this->createFeeBreakup(
            $rule->getFeature(),
            null,
            $fee,
            $rule);

        $this->feesSplit->push($rzpFee);

        return $fee;
    }

    private function getVASFees(int $units, $amount)
    {

        $rule = $this->pricingRules[0];

        $fees = $this->calculateRzpVASFee($rule, $units, $amount);

        $totalTaxes = $this->calculateTax($fees);

        $totalFees = $fees + $totalTaxes;

        return [$totalFees, $totalTaxes];
    }

    /**
     * @throws LogicException
     */
    public function calculateVASPrice(Pricing\Plan $pricing, string $feature, array $input): array
    {
        $method = $input[self::METHOD];
        $units = $input[self::UNITS];
        $amount = $input[self::AMOUNT];
        $frequency = $input[self::FREQUENCY];

        $this->getRelevantVASPricingRule($pricing, $feature, $method, $frequency);

        list($fee, $tax) = $this->getVASFees($units, $amount);

        return [$fee, $tax, $this->feesSplit];
    }

    /**
     * @throws LogicException
     */
    private function filterPricingRule(Pricing\Plan $pricing, string $feature, $method, $frequency)
    {
        /*
         * For VAS pricing, existing columns are reused and override
         * to avoid creating new column in pricing DB
         * https://docs.google.com/document/d/1vjwiYlCgKTZ9beFbeWdj1dYyXMmc6h3m5aOYr-wuHeU/edit?usp=sharing
         * feature              (feature)
         * payment_method       (sub feature)
         * payment_method_type  (pricing frequency)
         */
        $filter = [
            [Pricing\Entity::PRODUCT,                   Product::PRIMARY,   false,  null],
            [Pricing\Entity::FEATURE,                   $feature,           false,  null],
            [Pricing\Entity::PAYMENT_METHOD,            $method,            true,   null],
            [Pricing\Entity::PAYMENT_METHOD_TYPE,       $frequency,         true,   null],
        ];

        $rules = $this->applyFiltersOnRules($pricing, $filter);

        if (count($rules) == 0)
        {
            throw new Exception\LogicException(
                'No appropriate VAS pricing rule found',
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT);
        }

        $rule = parent::validateAndGetOnePricingRule($rules);

        $this->pricingRules->push($rule);
    }

    /**
     * @throws LogicException
     */
    private function getRelevantVASPricingRule(Pricing\Plan $pricing, string $feature, $method, $frequency): void
    {

        $this->filterPricingRule($pricing, $feature, $method, $frequency);

        $this->traceAllRules($this->pricingRules);

    }

}
