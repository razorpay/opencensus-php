<?php

namespace RZP\Models\Partner\Commission;

use App;
use RZP\Models\Pricing;
use RZP\Exception\LogicException;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

/***
 * Class CalculatorV2
 * This is a simple extended class to existing Commission calculator. We are updating the calculator variables in a different way
 * without resolving those details from payment and entity origin as in Calculator class
 * @package RZP\Models\Partner\Commission
 */
class CalculatorV2 extends Calculator
{
    protected bool $shouldCreditGst = true;

    public function __construct(CommissionSourceInterface $sourceEntity, array $partnerConfig, $subMerchant, $partner, array $partnerDetails)
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->env = $this->app['env'];

        $this->trace = $this->app['trace'];

        $this->config = $this->app['config'];

        $this->repo = $this->app['repo'];

        $this->cache = $this->app['cache'];

        $this->setSource($sourceEntity);

        $this->setCommissionPricingDetails($partnerConfig);

        $this->partner = $partner;

        $this->setTaxComponents($partnerDetails['tax_components']);

        $this->subMerchant = $subMerchant;
    }

    /**
     *This function is used to evaluate whether to credit the gst in commission or not and return the commission components
     * The caller should resolve this value and pass to the calculator as param
     * This overriden function is to avoid db calls to fetch gstin from merchant details entity
     * @param int $commissionFee
     * @param int $commissionTax
     *
     * @return int[]
     */
    protected function getCommissionFeeTax(int $commissionFee, int $commissionTax): array
    {
        if ($this->shouldCreditGst === false)
        {
            $commissionFee -= $commissionTax;
            $commissionTax = 0;
        }

        return [$commissionFee, $commissionTax];
    }

    /**
     * This function is used to update the variables that are needed for commission calculation. The resolution of these
     * details should derived from the caller (i.e. partnerships service here)
     * @param array $partnerConfig
     *
     */
    protected function setCommissionPricingDetails(array $partnerConfig)
    {
        $this->partnerConfig   = new PartnerConfig\Entity();
        $implicitPricingPlanId = $partnerConfig['implicit_plan_id'] ?? '';
        $explicitPricingPlanId = $partnerConfig['explicit_plan_id'] ?? '';
        $this->shouldCreditGst       = $partnerConfig['should_credit_gst'];
        if (empty($implicitPricingPlanId) == false)
        {
            $this->partnerConfig->setImplicitPlanIdAttribute($implicitPricingPlanId);
            $this->implicitPricingPlan = $this->repo->pricing->getPlan($implicitPricingPlanId, skipOrgCheck: true);
        }
        if (empty($explicitPricingPlanId) == false)
        {
            $this->partnerConfig->setExplicitPlanIdAttribute($explicitPricingPlanId);
            $this->explicitPricingPlan = $this->repo->pricing->getPlan($explicitPricingPlanId, skipOrgCheck: true);
        }

        $this->partnerConfig->setCommissionModel($partnerConfig['commission_model']);
    }

    protected function setMerchantFees()
    {
        // see fee and tax from payment entity to avoid issue with pricing calculation
        $pricingFee = new Pricing\Fee;
        $payment = $this->getSource();

        list($fee, $tax, $feeSplit) = $pricingFee->calculateMerchantRZPFees($this->getSource());
        $merchantFee = $payment->getFee();
        $merchantTax = $payment->getTax();

        // modify amount value in fee split with name as payment
        $feeSplit = $feeSplit->map(function($split) use ($merchantFee, $merchantTax) {
            if ($split->getName() === $this->getSource()->getEntity())
            {
                $split->setAmount($merchantFee-$merchantTax);
            }
            else if ($split->getName() === FeeBreakupName::TAX)
            {
                $split->setAmount($merchantTax);
            }
            return $split;
        });

        $this->setMerchantFee($merchantFee);
        $this->setMerchantTax($merchantTax);
        $this->setMerchantFeeSplit($feeSplit);
        $paymentFee = $feeSplit->filter(function($split) {
            return ($split->getName() === $this->getSource()->getEntity());
        })->first();

        $this->merchantPricingComponents[Component\Entity::MERCHANT_PRICING_AMOUNT]       = $paymentFee->getAmount();
        $this->merchantPricingComponents[Component\Entity::MERCHANT_PRICING_PLAN_RULE_ID] = $paymentFee->getPricingRule();
        $merchantPricingRule                                                              = $this->repo->pricing->getPricingFromPricingId($paymentFee->getPricingRule());
        $this->merchantPricingComponents[Component\Entity::MERCHANT_PRICING_PERCENTAGE ]  = $merchantPricingRule->getPercentRate();
        $this->merchantPricingComponents[Component\Entity::MERCHANT_PRICING_FIXED]        = $merchantPricingRule->getFixedRate();
    }

    /**
     * Calculates all types of applicable commissions [implicit (fixed and variable), explicit (fixed)] and returns
     * them.
     *
     * @throws LogicException
     */
    public function calculateAndGetCommission()
    {

        $this->calculate();

        return [
            "commissions"           => $this->commissions,
            "commission_components" => $this->commissionComponents
        ];
    }

}
