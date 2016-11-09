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

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Pricing\Repository;
    }

    /**
     *  Used in testing to mock
     *  pricing repository
     */
    public function setPricingRepo($repo)
    {
        $this->repo = $repo;
    }

    public function getZeroPricingPlanRule($entity)
    {
        $feature = $entity->getEntity();

        $method = $entity->getMethod();

        return $this->repo->getZeroPricingPlanRuleForMethod($feature, $method)->getId();
    }

    public function calculateMerchantFees($entity)
    {
        $calculator = new FeeCalculator($entity, $this->repo);

        $pricingPlanId = $this->getPricingPlanId($entity->merchant);

        $pricing = $this->repo->getPricingPlanById($pricingPlanId);

        return $calculator->calculate($pricing);
    }

    public function calculateServiceTaxFromFees($entity, $fee)
    {
        // Solving these
        // rzpFee + servTax = totFee;
        // servTax = ST_PERC * rzpFee;
        //         = ST_PERC * (totFee - servTax);

        // servTax = ( ST_PERC * totFee ) / ( 100 + ST_PERC ) ;

        $calculator = new FeeCalculator($entity, $this->repo);

        $totalTax = $calculator->calculateServiceTaxesFromFees($fee);

        $feesSplit = $calculator->getFeesSplit();

        return [$totalTax, $feesSplit];
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
        $mode = \BasicAuth::getMode();

        // In live, pricing plan for merchant cannot be null.
        if ($mode === Mode::LIVE)
        {
            throw new Exception\LogicException(
                'No pricing plan assigned for merchant id: ' . $merchant->getKey());
        }

        // In test, we can return a default pricing plan if it's not set for merchant.
        return $this->defaultPricingPlan;
    }
}
