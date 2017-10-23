<?php

namespace RZP\Models\Pricing;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Pricing;

class Fee extends Base\Core
{
    use AtomFeeTrait;

    protected $trace;

    protected $repo;

    const DEFAULT_PRICING_PLAN_ID = '1hDYlICobzOCYt';

    const EMI_SUB_PRICING_PLAN_ID = '1EmiSubPricing';

    const DEFAULT_BANK_TRANSFER_PLAN_ID = '8gP5505KgDVWIh';

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Pricing\Repository;
    }

    /**
     *  Used in testing to mock
     *  pricing repository
     *
     * @param $repo
     */
    public function setPricingRepo($repo)
    {
        $this->repo = $repo;
    }

    public function getZeroPricingPlanRule($entity): Entity
    {
        $feature = $entity->getEntity();

        $method = $entity->getMethod();

        return $this->repo->getZeroPricingPlanRuleForMethod($feature, $method);
    }

    public function calculateMerchantFees($entity)
    {
        $calculator = new FeeCalculator($entity, $this->repo);

        $pricingPlanId = $this->getPricingPlanId($entity->merchant);

        $pricing = $this->repo->getPricingPlanById($pricingPlanId);

        $pricing = $this->addFallbackPricingRules($pricing);

        return $calculator->calculate($pricing);
    }

    /**
     * Merges fallback pricing plans for methods that
     * do not have a pricing rule defined for them.
     *
     * @param Plan $pricingPlan
     *
     * @return Plan
     */
    protected function addFallbackPricingRules(Plan $pricingPlan)
    {
        $emiSubPricing = $this->repo->getPricingPlanById(self::EMI_SUB_PRICING_PLAN_ID);

        $pricingPlan = $pricingPlan->merge($emiSubPricing);

        if ($pricingPlan->hasMethod(Payment\Method::BANK_TRANSFER) === false)
        {
            $bankTransferPricing = $this->repo->getPricingPlanById(self::DEFAULT_BANK_TRANSFER_PLAN_ID);

            $pricingPlan = $pricingPlan->merge($bankTransferPricing);
        }

        return $pricingPlan;
    }

    protected function getPricingPlanId($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        if ($pricingPlanId !== null)
        {
            return $pricingPlanId;
        }

        return $this->getDefaultPricingPlan($merchant);
    }

    protected function getDefaultPricingPlan($merchant)
    {
        $mode = $this->app['basicauth']->getMode();

        // In live, pricing plan for merchant cannot be null.
        if ($mode === Mode::LIVE)
        {
            throw new Exception\LogicException(
                'No pricing plan assigned for merchant id: ' . $merchant->getKey());
        }

        // In test, we can return a default pricing plan if it's not set for merchant.
        return self::DEFAULT_PRICING_PLAN_ID;
    }
}
