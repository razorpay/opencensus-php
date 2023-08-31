<?php

namespace RZP\Models\Pricing;

use RZP\Constants\Environment;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Exception\LogicException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\Channel;
use RZP\Models\Partner\Commission;
use RZP\Models\Feature\Constants as Feature;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

use Carbon\Carbon;

class Fee extends Base\Core
{
    use AtomFeeTrait;

    protected $trace;

    protected $repo;

    const DEFAULT_PRICING_PLAN_ID            = '1hDYlICobzOCYt';
    const EMI_SUB_PRICING_PLAN_ID            = '1EmiSubPricing';
    const DEFAULT_QR_CODE_PLAN_ID            = 'A8UwvIbaL8n4Q8';
    const DEFAULT_EMI_PLAN_ID                = 'ArGUUem5z3UADv';
    const DEFAULT_BANK_TRANSFER_PLAN_ID      = '8gP5505KgDVWIh';
    const DEFAULT_BANKING_PLAN_ID            = 'BTo98voDY05ueB';
    const DEFAULT_VIRTUAL_UPI_PLAN_ID        = 'E9t4ljLBnt2cad';
    const DEFAULT_INSTANT_REFUNDS_PLAN_ID    = 'EIccfYpbLnrp6E';
    const DEFAULT_INSTANT_REFUNDS_PLAN_V2_ID = 'F3HF3mQrxjvSnm';
    const DEFAULT_AFFORDABILITY_WIDGET_PLAN_ID = 'L4teuQy3rngjPm';
    const DEFAULT_CC_ON_UPI_PLAN_ID            = 'Lwxtwg54MYaNTw';

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Pricing\Repository;
    }

    public function setMerchant(Merchant\Entity $merchant)
    {
        $this->merchant = $merchant;
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

    public function getInstantRefundsDefaultPricingPlanForMethod($entity)
    {
        $feature = EntityConstants::REFUND;

        $method = $entity->getMethod();

        $planId = Fee::DEFAULT_INSTANT_REFUNDS_PLAN_ID;

        $merchantId = $entity->merchant->getId();

        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::INSTANT_REFUNDS_DEFAULT_PRICING_V1,
            $this->mode
        );

        //
        // Instant Refunds v2 pricing is now default - not behind a razorx anymore
        // Instant Refunds v1 Pricing is behind razorx for merchants in transition phase
        //
        if ($variant !== RefundConstants::RAZORX_VARIANT_ON)
        {
            $planId = Fee::DEFAULT_INSTANT_REFUNDS_PLAN_V2_ID;
        }

        return $this->repo->getInstantRefundsDefaultPricingPlanForMethod(
            $feature,
            $method,
            Product::PRIMARY,
            $planId
        );
    }

    /**
     * Returns total merchant fees for entity which includes RZP fees and partner fees if any
     *
     * @param $entity
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function calculateMerchantFees($entity): array
    {
        $startTimeMs = round(microtime(true) * 1000);

        list($rzpFee, $rzpTax, $rzpFeeSplit) = $this->calculateMerchantRZPFees($entity);

        list($partnerFee, $partnerTax, $partnerFeeSplit) = $this->calculatePartnerFees($entity);

        $totalFee = $rzpFee + $partnerFee;

        $totalTax = $rzpTax + $partnerTax;

        $feeSplit = $rzpFeeSplit->concat($partnerFeeSplit);

        $this->validateFees($entity, $totalFee);

        $endTimeMs = round(microtime(true) * 1000);
        $timeTaken = $endTimeMs - $startTimeMs;
        $this->trace->histogram(Metrics::PRICING_FEE_CALCULATION_TIME_IN_MS, $timeTaken);

        return [$totalFee, $totalTax, $feeSplit];
    }

    public function calculateTerminalFees($entity, $pricing): array
    {
        list($rzpFee, $rzpTax, $rzpFeeSplit) = $this->calculateTerminalRZPFees($entity, $pricing);

        return [$rzpFee, $rzpTax, $rzpFeeSplit];
    }

    public function calculateVASFees(array $input, string $merchant_id, $feature): array
    {
        list($rzpFee, $rzpTax, $rzpFeeSplit) = $this->calculateVASRZPFees($input, $merchant_id, $feature);

        return [$rzpFee, $rzpTax, $rzpFeeSplit];
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

        $pricingPlanId = $this->getPricingPlanId($entity);

        // Delete this after 31st Jan
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $merchant = $entity->merchant;

        $pricing = $this->repo->getPricingPlanByIdWithoutOrgId($pricingPlanId, $merchant);

        $pricing = $this->addFallbackPricingRules($pricing, $entity);

        return $calculator->calculate($pricing);
    }

    public function calculateTerminalRZPFees(Payment\Entity $entity, Pricing\Plan $pricing): array
    {
        $calculator = Calculator\Base::make($entity, Product::PRIMARY, 'terminal');

        return $calculator->calculate($pricing);
    }

    public function getMerchantEntity($merchant_id){
        $merchant_entity = (new \RZP\Models\Merchant\Repository())->getMerchant($merchant_id);

        $entity = new PublicEntity();
        $entity->merchant = $merchant_entity;
        $this->trace->info(
            TraceCode::VAS_PRICING_FETCH_REQUEST,
            [
                'Merchant Plan Id :' => $merchant_entity->getPricingPlanId(),
            ]);

        return $entity;
    }

    /**
     * @throws LogicException
     * @throws ServerErrorException
     */
    public function calculateVASRZPFees(array $input, string $merchant_id, $feature): array
    {
        try {
            $entity = $this->getMerchantEntity($merchant_id);
        } catch (\Exception)
        {
            throw new Exception\ServerErrorException("Merchant Entity Fetch Failed for MID: $merchant_id" ,ErrorCode::SERVER_ERROR_MERCHANT_ENTITY_FETCH_FAILED);
        }

        $calculator = new Calculator\PayAsYouGo($entity, Product::PRIMARY);

        $pricingPlanId = $entity->merchant->getPricingPlanId();

        $pricing = $this->repo->getPricingRulesByPlanIdProductAndFeatureWithoutOrgId($pricingPlanId, Product::PRIMARY, $feature);

        return $calculator->calculateVASPrice($pricing, $feature, $input);
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
     * @param Plan              $pricingPlan
     * @param PublicEntity $entity
     *
     * @return Plan
     */
    public function addFallbackPricingRules(Plan $pricingPlan, PublicEntity $entity)
    {
        $merchant = $entity->merchant;

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

        if ($pricingPlan->hasVpaReceiver() === false)
        {
            $virtualUpiPricing = $this->repo->getPricingPlanByIdWithoutOrgId(self::DEFAULT_VIRTUAL_UPI_PLAN_ID);

            $pricingPlan = $pricingPlan->merge($virtualUpiPricing);
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

        if ($pricingPlan->hasCreditReceiver() === false)
        {
                $ccOnUPIPricing = $this->repo->getPricingPlanByIdWithoutOrgId(self::DEFAULT_CC_ON_UPI_PLAN_ID);

                $pricingPlan = $pricingPlan->merge($ccOnUPIPricing);
        }

        if ($pricingPlan->hasMethod(Payment\Method::EMI) === false)
        {
            $emiPricing = $this->repo->getPricingPlanByIdWithoutOrgId(self::DEFAULT_EMI_PLAN_ID);

            $pricingPlan = $pricingPlan->merge($emiPricing);
        }

        if ($pricingPlan->hasMethod(Payment\Method::COD) === false)
        {
            $pricingPlan = $this->addDefaultCoDPricingRules($pricingPlan);
        }

        if ($pricingPlan->hasMethod(Payment\Method::INTL_BANK_TRANSFER) === false)
        {
            $pricingPlan = $this->addDefaultIntlBankTransferPricingRules($pricingPlan);
        }

        $pricingPlan = $this->addBankingFallbackRulesIfApplicable($pricingPlan, $entity);

        return $pricingPlan;
    }

    protected function addBankingFallbackRulesIfApplicable(Plan $pricingPlan, PublicEntity $entity)
    {
        $merchant = $entity->merchant;

        //
        // Business banking rules are applied only business banking is enabled and feature is payout
        //
        if (($entity->getEntityName() !== EntityConstants::PAYOUT) or
            ($merchant->isBusinessBankingEnabled() === false))
        {
            return $pricingPlan;
        }

        $pricingPlan = $this->addNonAppBankingPayoutFallbackRules($pricingPlan, $merchant);

        $pricingPlan = $this->addBankingPayoutAppFallbackRules($pricingPlan, $merchant);

        return $pricingPlan;
    }

    protected function addDefaultCoDPricingRules($pricingPlan)
    {
        $id = $this->app['config']->get('pricing.cod.default_rule_id');

        if (empty(($id)))
        {
            return $pricingPlan;
        }

        $codPricing = $this->repo->getPricingPlanByIdWithoutOrgId($id);

        if ($codPricing === null)
        {
            return  $pricingPlan;
        }

        $pricingPlan = $pricingPlan->merge($codPricing);

        return $pricingPlan;
    }

    protected function addDefaultIntlBankTransferPricingRules($pricingPlan)
    {
        if($this->app['env'] === Environment::TESTING)
        {
            $id = $this->app['config']->get('pricing.IntlBankTransfer.default_rule_id');

            if (empty(($id)))
            {
                return $pricingPlan;
            }

            $intlBankTransferPricing = $this->repo->getPricingPlanByIdWithoutOrgId($id);

            if ($intlBankTransferPricing === null)
            {
                return  $pricingPlan;
            }

            $pricingPlan = $pricingPlan->merge($intlBankTransferPricing);
        }
        return $pricingPlan;
    }

    protected function addNonAppBankingPayoutFallbackRules(Plan $pricingPlan, Merchant\Entity $merchant)
    {
        //
        // Add default pricing rules with payouts_filter = free_payout, only when no such rules are already defined for
        // Shared accounts.
        // If ANY custom pricing rules for payouts_filter = free_payout have been added for banking payouts, we do not
        // attach default pricing rules where payouts_filter = free_payout
        //
        if ($pricingPlan->hasBankingSharedAccountFreePayoutRule() === false)
        {
            $rules       = $this->repo->getBankingSharedAccountFreePayoutDefaultPricingRules(Feature::PAYOUT, $merchant);
            $pricingPlan = $pricingPlan->merge($rules);
        }

        //
        // Add default pricing rules, only when no rules are already defined for Shared accounts.
        // If ANY custom pricing rules have been added for banking payouts, we do not attach
        // default pricing rules
        //
        if ($pricingPlan->hasBankingSharedAccountNonFreePayoutRule() === false)
        {
            $rules       = $this->repo->getBankingSharedAccountNonFreePayouDefaultPricingRules(Feature::PAYOUT, $merchant);
            $pricingPlan = $pricingPlan->merge($rules);
        }

        //
        // Add default pricing rules with payouts_filter = free_payout, only when no such rules are already defined for
        // Direct accounts.
        // If ANY custom pricing rules for payouts_filter = free_payout have been added for banking payouts, we do not
        // attach default pricing rules where payouts_filter = free_payout
        //
        if ($pricingPlan->hasBankingDirectAccountFreePayoutRule() === false)
        {
            $rules       = $this->repo->getBankingDirectAccountFreePayoutDefaultPricingRules(Feature::PAYOUT, $merchant);
            $pricingPlan = $pricingPlan->merge($rules);
        }

        //
        // Add default pricing rules, only when no rules are already defined for Direct accounts.
        // If ANY custom pricing rules have been added for banking payouts, we do not attach
        // default pricing rules
        //
        $directChannelsWithRulesAbsent = [];

        list($rblRulePresent, $iciciRulePresent, $axisRulePresent, $yesbankRulePresent) = $pricingPlan->hasBankingDirectAccountNonFreePayoutRule();

        if ($rblRulePresent === false)
        {
            $directChannelsWithRulesAbsent[] = Channel::RBL;
        }

        if ($iciciRulePresent === false)
        {
            $directChannelsWithRulesAbsent[] = Channel::ICICI;
        }

        if ($axisRulePresent === false)
        {
            $directChannelsWithRulesAbsent[] = Channel::AXIS;
        }

        if ($yesbankRulePresent === false)
        {
            $directChannelsWithRulesAbsent[] = Channel::YESBANK;
        }

        if (empty($directChannelsWithRulesAbsent) === false)
        {
            $rules = $this->repo->getBankingDirectAccountNonFreePayoutDefaultPricingRules(
                Feature::PAYOUT,
                $merchant,
                $directChannelsWithRulesAbsent);

            $pricingPlan = $pricingPlan->merge($rules);
        }

        return $pricingPlan;
    }

    protected function getPricingPlanId($entity)
    {
        $customPricingPlan = $this->getCustomPricingPlan($entity);

        if (empty($customPricingPlan) === false)
        {
            return $customPricingPlan;
        }

        $merchant = $entity->merchant;

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

    public function getProductForEntity(PublicEntity $entity): string
    {
        // Source entities which creates transaction on multiple balance have balance itself.

        if (method_exists($entity, 'hasBalance') === true)
        {
            $balanceType = $entity->getBalanceType();

            return ($balanceType === Balance\Type::BANKING) ? Product::BANKING : Product::PRIMARY;
        }

        return Product::PRIMARY;
    }

    protected function getCustomPricingPlan(PublicEntity $entity)
    {
        return null;
    }

    protected function addBankingPayoutAppFallbackRules(Plan $pricingPlan, Merchant\Entity $merchant)
    {
        // add app specific pricing rules, if they are already not included
        if ($pricingPlan->hasAppPayoutPricingRule() === false)
        {
            $rules       = $this->repo->getAppPayoutPricingRules(Feature::PAYOUT, $merchant);
            $pricingPlan = $pricingPlan->merge($rules);
        }

        return $pricingPlan;
    }
}
