<?php

namespace Models\Pricing;

use Constants\Mode;
use EE\Exception;
use Models\Card;
use Models\Payment;
use Models\Pricing;
use Services\SlackPoster;
use Trace\Trace;
use Trace\TraceCode;

class Fee
{
    use SlackPoster;
    use AtomFeeTrait;

    const SERVICE_TAX_PERCENT = 15.0;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function __construct()
    {
        $this->repo = new Pricing\Repository;

        $this->trace = \Trace::getFacadeRoot();
    }

    /**
     *  Used in testing to mock
     *  pricing repository
     */
    public function setPricingRepo($repo)
    {
        $this->repo = $repo;
    }

    public function getZeroPricingPlanRule($payment)
    {
        $planId = Pricing\Entity::ZERO_PRICING;

        $method = $payment->getMethod();

        return $this->repo->getZeroPricingPlanRuleForMethod($method)->getId();
    }

    public function calculateMerchantFees($payment, $preCalculationOfFees = false)
    {
        $calculator = new FeeCalculator($payment, $this->repo);

        $pricingPlanId = $this->getPricingPlanId($payment->merchant);

        $pricing = $this->repo->getPricingPlanById($pricingPlanId);

        return $calculator->calculate($pricing, $preCalculationOfFees);
    }

    public function calculateServiceTax($txn, $payment)
    {
        $rule = $this->repo->getPricingPlanRule($txn->getPricingRule());

        $txnAuthTime = $payment->getAuthorizeTimestamp();

        // Set the authorized_at time if not set
        if (is_null($txnAuthTime) === True)
        {
            $txnCreatedTime = $payment->getCreatedTimestamp();
            $txnCapturedTime = $payment->getCaptureTimestamp();

            assert(is_null($txnCreatedTime) === FALSE);
            assert(is_null($txnCapturedTime) === FALSE);

            $txnAuthTime = ($txnCreatedTime + 45);

            $payment->setAuthorizeTimestamp($txnAuthTime);
        }

        list($fee, $serviceTax) = $this->getFees($rule, $payment->getAmount(), 0);

        $serviceTax = $txn->getFee() - $fee;
        assert($serviceTax > 0);

        return $serviceTax;
    }

    public function calculateServiceTaxFromFees($fee)
    {
        // Solving these
        // rzpFee + servTax = totFee;
        // servTax = ST_PERC * rzpFee;
        //         = ST_PERC * (totFee - servTax);

        // servTax = ( ST_PERC * totFee ) / ( 100 + ST_PERC ) ;

        $numerator = self::SERVICE_TAX_PERCENT * $fee ;

        $denominator = 100 + self::SERVICE_TAX_PERCENT ;

        return ceil($numerator / $denominator);
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
