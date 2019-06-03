<?php

namespace RZP\Models\Pricing;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Models\Partner\Commission;
use RZP\Models\Feature\Constants as Feature;

use Carbon\Carbon;

class Fee extends Base\Core
{
    use AtomFeeTrait;

    protected $trace;

    protected $repo;

    const DEFAULT_PRICING_PLAN_ID       = '1hDYlICobzOCYt';
    const EMI_SUB_PRICING_PLAN_ID       = '1EmiSubPricing';
    const DEFAULT_QR_CODE_PLAN_ID       = 'A8UwvIbaL8n4Q8';
    const DEFAULT_EMI_PLAN_ID           = 'ArGUUem5z3UADv';
    const DEFAULT_BANK_TRANSFER_PLAN_ID = '8gP5505KgDVWIh';
    const DEFAULT_BANKING_PLAN_ID       = 'BTo98voDY05ueB';

    // Delete this after 31st Jan
    const DIWALI_END_TIMESTAMP = 1548916199;

    // Delete this after 31st Jan
    protected static $promotionalMethods = [
        'card',
        'emi',
        'netbanking',
        'upi',
        'wallet',
    ];

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

        return $this->repo->getZeroPricingPlanRuleForMethod($feature, $method, $entity->merchant);
    }

    /**
     * Returns total merchant fees for entity which includes RZP fees and partner fees if any
     *
     * @param $entity
     *
     * @return array
     */
    public function calculateMerchantFees($entity): array
    {
        list($rzpFee, $rzpTax, $rzpFeeSplit) = $this->calculateMerchantRZPFees($entity);

        list($partnerFee, $partnerTax, $partnerFeeSplit) = $this->calculatePartnerFees($entity);

        $totalFee = $rzpFee + $partnerFee;
        
        $totalTax = $rzpTax + $partnerTax;

        $feeSplit = $rzpFeeSplit->concat($partnerFeeSplit);

        $this->validateFees($entity, $totalFee);

        return [$totalFee, $totalTax, $feeSplit];
    }

    /**
     * Returns merchant RZP fees for entity
     *
     * @param $entity
     *
     * @return array
     */
    public function calculateMerchantRZPFees($entity): array
    {
        $calculator = $this->getCalculator($entity);

        $pricingPlanId = $this->getPricingPlanId($entity->merchant);

        // Delete this after 31st Jan
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $merchant = $entity->merchant;

        if ((($entity instanceof Payment\Entity) === true) and
            ($merchant->isFeatureEnabled(Feature::DIWALI_PROMOTIONAL_PLAN) === true) and
            ($currentTimeStamp < self::DIWALI_END_TIMESTAMP) and
            (in_array($entity->getMethod(), self::$promotionalMethods, true) === true))
        {
            $pricingPlanId = Pricing\DefaultPlan::DIWALI_PROMOTIONAL_PLAN_ID;
        }

        $pricing = $this->repo->getPricingPlanByIdWithoutOrgId($pricingPlanId);

        $pricing = $this->addFallbackPricingRules($pricing, $entity->merchant);

        return $calculator->calculate($pricing);
    }

    protected function getCalculator($entity)
    {
        $product = $this->getProductForEntity($entity);

        return Calculator\Base::make($entity, $product);
    }

    /**
     * Returns partner fees for entity
     *
     * @param $entity
     *
     * @return array
     */
    protected function calculatePartnerFees($entity): array
    {
        $feeDetails = [0, 0, new Base\PublicCollection];

        // charge partner fees for only some type of entities
        if (Commission\Constants::isValidCommissionSource($entity) === false)
        {
            return $feeDetails;
        }

        try
        {
            $calculator = new Commission\Calculator($entity);

            if ($calculator->shouldChargePartnerFees() === false)
            {
                return $feeDetails;
            }

            list($partnerFees, $partnerTax, $feeSplit) = $calculator->getExplicitCommissionFeeSplit();

            $this->trace->info(
                TraceCode::COMMISSION_EXPLICIT_FEE_BREAKUP_LOGGED,
                [
                    'partner_fees' => $partnerFees,
                    'partner_tax'  => $partnerTax,
                    'fee_split'    => $feeSplit->toArrayPublic(),
                ]);

            return [$partnerFees, $partnerTax, $feeSplit];
        }
        catch (\Throwable $ex)
        {
            $this->trace->critical(
                TraceCode::COMMISSION_EXPLICIT_FEE_BREAKUP_CREATE_FAILED,
                [
                    'id'      => $entity->getId(),
                    'type'    => $entity->getEntityName(),
                    'message' => $ex->getMessage(),
                ]
            );
        }

        return $feeDetails;
    }

    /**
     * Check whether the fees is valid based on entity type
     *
     * @param $entity
     * @param $totalFees
     *
     * @throws Exception\BadRequestException
     */
    protected function validateFees($entity, $totalFees)
    {
        $calculator = $this->getCalculator($entity);

        $calculator->validateFees($totalFees);
    }

    /**
     * Merges fallback pricing plans for methods that
     * do not have a pricing rule defined for them.
     *
     * @param Plan            $pricingPlan
     * @param Merchant\Entity $merchant
     *
     * @return Plan
     */
    protected function addFallbackPricingRules(Plan $pricingPlan, Merchant\Entity $merchant)
    {
        // for other orgs we don't merge any pricing plans
        if ($merchant->org->getId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return $pricingPlan;
        }

        $emiSubPricing = $this->repo->getPricingPlanByIdWithoutOrgId(self::EMI_SUB_PRICING_PLAN_ID);

        $pricingPlan = $pricingPlan->merge($emiSubPricing);

        if ($pricingPlan->hasMethod(Payment\Method::BANK_TRANSFER) === false)
        {
            $bankTransferPricing = $this->repo->getPricingPlanByIdWithoutOrgId(self::DEFAULT_BANK_TRANSFER_PLAN_ID);

            $pricingPlan = $pricingPlan->merge($bankTransferPricing);
        }

        if ($pricingPlan->hasQrCodeReceiver() === false)
        {
            //
            // We are not creating the default qr code pricing plan in code because it has multiple
            // issue.
            // 1. We wouldn't be able to change the pricing rules without changing the code. It will
            //    need a deployment
            // 2. If we add it in the code we will have to keep validation on deletion. Because if
            //    a ops guy deletes it it will get created again.
            //
            $qrCodePricing = $this->repo->getPricingPlanByIdWithoutOrgId(self::DEFAULT_QR_CODE_PLAN_ID);

            $pricingPlan = $pricingPlan->merge($qrCodePricing);
        }

        if ($pricingPlan->hasMethod(Payment\Method::EMI) === false)
        {
            $emiPricing = $this->repo->getPricingPlanByIdWithoutOrgId(self::DEFAULT_EMI_PLAN_ID);

            $pricingPlan = $pricingPlan->merge($emiPricing);
        }

        $pricingPlan = $this->addBankingFallbackRulesIfApplicable($pricingPlan, $merchant);

        return $pricingPlan;
    }

    protected function addBankingFallbackRulesIfApplicable(Plan $pricingPlan, Merchant\Entity $merchant)
    {
        if ($merchant->isBusinessBankingEnabled() === false)
        {
            return $pricingPlan;
        }

        //
        // Add default pricing rules, only when no rules are already defined.
        // If ANY custom pricing rules have been added for banking payouts, we do not attach
        // default pricing rules
        //
        if ($pricingPlan->hasBankingPayoutRule() === false)
        {
            $rules       = $this->repo->getBankingDefaultPricingRules(Feature::PAYOUT, $merchant);
            $pricingPlan = $pricingPlan->merge($rules);
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

    public function getProductForEntity(Base\PublicEntity $entity): string
    {
        // Source entities which creates transaction on multiple balance have balance itself.

        if (method_exists($entity, 'hasBalance') === true)
        {
            $balanceType = $entity->getBalanceType();

            return ($balanceType === Balance\Type::BANKING) ? Product::BANKING : Product::PRIMARY;
        }

        return Product::PRIMARY;
    }
}
