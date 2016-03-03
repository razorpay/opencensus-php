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

    const SERVICE_TAX_PERCENT = 14.5;

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

    public function calculateMerchantFees($payment)
    {
        $calculator = new FeeCalculator($payment, $this->repo);

        return $calculator->calculate();
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

    protected function getUnroundedFees($amount, $percent, $fixed)
    {
        return (($amount * $percent) / 10000) + $fixed;
    }
}
