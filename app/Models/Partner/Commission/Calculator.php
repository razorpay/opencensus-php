<?php

namespace RZP\Models\Partner\Commission;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\EntityOrigin;
use RZP\Models\Pricing\Plan;
use RZP\Exception\LogicException;
use RZP\Models\Partner\Commission;
use Razorpay\OAuth\Application as OAuthApp;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Pricing\Calculator as FeeCalculator;

/**
 * Class Calculator
 *
 * @package RZP\Models\Partner\Commission
 */
class Calculator extends Base\Core
{
    /**
     * @var Merchant\Entity
     */
    protected $subMerchant;

    /**
     * @var Merchant\Entity|null
     */
    protected $partner = null;

    /**
     * @var OAuthApp\Entity|null
     */
    protected $partnerApp = null;

    /**
     * An entity that implemnents the CommissionSourceInterface - payment, refund etc
     *
     * @var Base\PublicEntity
     */
    protected $source;

    /**
     * @var PartnerConfig\Entity|null
     */
    protected $partnerConfig = null;

    /**
     * @var int
     */
    protected $commissionFee = 0;

    /**
     * @var int
     */
    protected $commissionTax = 0;

    /**
     * @var int
     */
    protected $merchantFee = 0;

    /**
     * @var int
     */
    protected $merchantTax = 0;

    /**
     * @var int
     */
    protected $partnerFee = 0;

    /**
     * @var int
     */
    protected $partnerTax = 0;

    /**
     * @var array
     */
    protected $commissions = [];

    /**
     * @var null
     */
    protected $implicitPricingPlan = null;

    /**
     * @var
     */
    protected $partnerConfigCore;

    /**
     * @var null|FeeCalculator\Base
     */
    protected $feeCalculator = null;

    /**
     * Calculator constructor.
     *
     * @param Base\PublicEntity $sourceEntity
     */
    public function __construct(Base\PublicEntity $sourceEntity)
    {
        parent::__construct();

        if (Constants::isValidCommissionSource($sourceEntity) === false)
        {
            return;
        }

        $this->partnerConfigCore = new PartnerConfig\Core;

        $this->setBaseContext($sourceEntity);
    }

    // ==================================== GETTERS ====================================

    /**
     * @return Merchant\Entity
     */
    public function getSubMerchant(): Merchant\Entity
    {
        return $this->subMerchant;
    }

    /**
     * @return Base\PublicEntity
     */
    public function getSource(): Base\PublicEntity
    {
        return $this->source;
    }

    /**
     * Partner property will be set to null if the partner does not exist.
     *
     * @return Merchant\Entity|null
     */
    public function getPartner()
    {
        return $this->partner;
    }

    /**
     * Partner config property will be set to null if the partner does not exist.
     *
     * @return PartnerConfig\Entity|null
     */
    public function getPartnerConfig()
    {
        return $this->partnerConfig;
    }

    /**
     * Partner app property will be set to null if the partner does not exist.
     *
     * @return OAuthApp\Entity|null
     */
    public function getPartnerApp()
    {
        return $this->partnerApp;
    }

    /**
     * Implicit pricing plan property will be set to null if the partner does not exist.
     *
     * @return Plan|null
     */
    public function getImplicitPricingPlan()
    {
        return $this->implicitPricingPlan;
    }

    /**
     * Returns the list of commission entities created
     *
     * @return array
     */
    public function getCommissions(): array
    {
        return $this->commissions;
    }

    /**
     * @return int
     */
    public function getCommissionFee(): int
    {
        return $this->commissionFee;
    }

    /**
     * @return int
     */
    public function getCommissionTax(): int
    {
        return $this->commissionTax;
    }

    /**
     * @return int
     */
    public function getMerchantFee(): int
    {
        return $this->merchantFee;
    }

    /**
     * @return int
     */
    public function getMerchantTax(): int
    {
        return $this->merchantTax;
    }

    /**
     * @return int
     */
    public function getPartnerFee(): int
    {
        return $this->partnerFee;
    }

    /**
     * @return int
     */
    public function getPartnerTax(): int
    {
        return $this->partnerTax;
    }

    public function getFeeCalculator(): FeeCalculator\Base
    {
        if ($this->feeCalculator === null)
        {
            $pricingFee = new Pricing\Fee;

            // primary or banking
            $product = $pricingFee->getProductForEntity($this->getSource());

            $this->feeCalculator = FeeCalculator\Base::make($this->getSource(), $product);
        }

        return $this->feeCalculator;
    }

    // ==================================== SETTERS ====================================

    /**
     * @param Base\PublicEntity $source
     */
    public function setSource(Base\PublicEntity $source)
    {
        $this->source = $source;
    }

    /**
     * @param Merchant\Entity $partner
     */
    public function setPartner(Merchant\Entity $partner)
    {
        $this->partner = $partner;
    }

    /**
     * @param OAuthApp\Entity $partnerApp
     */
    public function setPartnerApp(OAuthApp\Entity $partnerApp)
    {
        $this->partnerApp = $partnerApp;
    }

    /**
     * @param Merchant\Entity $subMerchant
     */
    public function setSubMerchant(Merchant\Entity $subMerchant)
    {
        $this->subMerchant = $subMerchant;
    }

    /**
     * @param PartnerConfig\Entity $partnerConfig
     */
    public function setPartnerConfig(PartnerConfig\Entity $partnerConfig)
    {
        $this->partnerConfig = $partnerConfig;
    }

    /**
     * @param Plan $pricingPlan
     */
    public function setImplicitPricingPlan(Plan $pricingPlan)
    {
        $this->implicitPricingPlan = $pricingPlan;
    }

    /**
     * @param int $fee
     */
    public function setCommissionFee(int $fee)
    {
        $this->commissionFee = $fee;
    }

    /**
     * @param int $tax
     */
    public function setCommissionTax(int $tax)
    {
        $this->commissionTax = $tax;
    }

    /**
     * @param int $merchantFee
     */
    public function setMerchantFee(int $merchantFee)
    {
        $this->merchantFee = $merchantFee;
    }

    /**
     * @param int $merchantTax
     */
    public function setMerchantTax(int $merchantTax)
    {
        $this->merchantTax = $merchantTax;
    }

    /**
     * @param int $partnerFee
     */
    public function setPartnerFee(int $partnerFee)
    {
        $this->partnerFee = $partnerFee;
    }

    /**
     * @param int $partnerTax
     */
    public function setPartnerTax(int $partnerTax)
    {
        $this->partnerTax = $partnerTax;
    }

    // ====================================== END ======================================

    /**
     * @param Base\PublicEntity $sourceEntity
     */
    protected function setBaseContext(Base\PublicEntity $sourceEntity)
    {
        // payment, refund etc
        $this->setSource($sourceEntity);

        // partner's submerchant that this source entity belongs to
        $this->setSubMerchant($this->getSource()->merchant);

        // partner's internal oauth app
        $this->setPartnerAppContext();

        // partner merchant
        $this->setPartnerContext();

        // partner merchant's oauth application's configuration (overridden for this submerchant)
        $this->setPartnerConfigContext();

        // configuration's implicit pricing plan
        $this->setImplicitPricingPlanContext();
    }

    /**
     * @return bool
     */
    protected function shouldCreateCommission(): bool
    {
        if (Constants::isValidCommissionSource($this->getSource()) === false)
        {
            $this->traceContext(TraceCode::COMMISSION_INVALID_SOURCE_ENTITY);

            return false;
        }

        if ($this->isCommissionApplicable() === false)
        {
            $this->traceContext(
                TraceCode::COMMISSION_NOT_APPLICABLE,
                [
                    'customer_fee_bearer' => $this->isCustomerFeeBearer(),
                    'implicit_plan_type'  => optional($this->getImplicitPricingPlan())->getType(),
                    'fee_model_prepaid'   => $this->getSubMerchant()->isPrepaid(),
                ]);

            return false;
        }

        if ($this->isCommissionsEnabled() === false)
        {
            $this->traceContext(TraceCode::COMMISSION_NOT_ENABLED);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isCommissionApplicable(): bool
    {
        if ($this->getPartner() === null)
        {
            return false;
        }

        if ($this->getPartnerConfig() === null)
        {
            return false;
        }

        if ($this->getImplicitPricingPlan() === null)
        {
            return false;
        }

        // Blocks create commission if the conditions are not supported, from here -

        if ($this->getImplicitPricingPlan()->isTypePricing() === false)
        {
            return false;
        }

        if ($this->isCustomerFeeBearer() === true)
        {
            return false;
        }

        if ($this->getSubMerchant()->isPrepaid() === false)
        {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isCustomerFeeBearer(): bool
    {
        return ($this->getSubMerchant()->isFeeBearerCustomer() === true);
    }

    /**
     * @return bool
     */
    protected function isCommissionsEnabled(): bool
    {
        return ($this->getPartnerConfig()->isCommissionsEnabled() === true);
    }

    /**
     * @param Entity $commission
     */
    protected function addCommission(Commission\Entity $commission)
    {
        $this->commissions[] = $commission;
    }

    /**
     * Calculates all types of applicable commissions [implicit (fixed and variable), explicit (fixed)]
     * and updates the class property - $this->commissions.
     */
    public function calculate()
    {
        if ($this->shouldCreateCommission() === false)
        {
            return;
        }

        $this->buildImplicitVariableCommissionEntities();
    }

    /**
     * Calculates all types of applicable commissions [implicit (fixed and variable), explicit (fixed)] and saves them.
     * This function should be called within a database transaction.
     */
    public function calculateAndSaveCommission()
    {
        if ($this->shouldCreateCommission() === false)
        {
            return;
        }

        $this->calculate();

        $this->saveCommission();
    }

    /**
     * Saves the list of commission entities built so far
     */
    protected function saveCommission()
    {
        if (empty($this->commissions) === true)
        {
            return;
        }

        foreach ($this->commissions as $commission)
        {
            $this->updateStatus($commission);

            $this->repo->saveOrFail($commission);
        }

        $this->traceContext(TraceCode::COMMISSION_CREATED);
    }

    protected function updateStatus(Commission\Entity $commission)
    {
        //
        // @todo: Add a check. Implicit commissions must be set processed once picked up, and, the status for the
        // explicit commissions must be updated based on the explicit_should_charge flag. Also, add fn desctiption.
        //
        $commission->setStatus(Status::RECORDED);
    }

    /**
     * For variable commissions, this will calculate the difference b/w the merchant pricing and the partner pricing.
     * For fixed commissions, this will use the fixed commission pricing to calculate the commissions.
     *
     * Once calculated, the commission entities will be added to the class property - $this->commissions
     *
     * @return null
     * @throws LogicException
     */
    protected function buildImplicitVariableCommissionEntities()
    {
        list($merchantFee, $merchantTax, $merchantFeesSplit) = $this->getMerchantFees();

        list($partnerFee, $partnerTax, $partnerSplit) = $this->getPartnerPricing();

        $this->setMerchantFee($merchantFee);
        $this->setMerchantTax($merchantTax);
        $this->setPartnerFee($partnerFee);
        $this->setPartnerTax($partnerTax);

        if ($partnerFee === 0)
        {
            $this->traceContext(
                TraceCode::COMMISSION_NOT_DEFINED,
                [
                    'merchant_fees' => $merchantFee,
                    'partner_fees'  => $partnerFee,
                    'merchant_tax'  => $merchantTax,
                    'partner_tax'   => $partnerTax,
                ]);

            return;
        }

        $commissionFee = $merchantFee - $partnerFee;
        $commissionTax = $merchantTax - $partnerTax;
        $this->setCommissionFee($commissionFee);
        $this->setCommissionTax($commissionTax);

        if ($commissionFee < 0)
        {
            $this->traceContext(
                TraceCode::COMMISSION_COMPUTED_NEGATIVE,
                [
                    'merchant_fees' => $merchantFee,
                    'partner_fees'  => $partnerFee,
                    'merchant_tax'  => $merchantTax,
                    'partner_tax'   => $partnerTax,
                ],
                Trace::CRITICAL);

            return;
        }

        if ($commissionFee === 0)
        {
            $this->traceContext(TraceCode::COMMISSION_COMPUTED_ZERO, [
                'merchant_fees' => $merchantFee,
                'partner_fees'  => $partnerFee,
                'merchant_tax'  => $merchantTax,
                'partner_tax'   => $partnerTax,
            ]);

            return;
        }

        $this->traceContext(TraceCode::COMMISSION_COMPUTED, [
            'merchant_fees'     => $merchantFee,
            'partner_fees'      => $partnerFee,
            'merchant_tax'      => $merchantTax,
            'partner_tax'       => $partnerTax,
            'commission_amount' => $commissionFee,
            'commission_tax'    => $commissionTax,
        ]);

        $payload = $this->getCreateCommissionPayload();

        $commission = (new Commission\Core)->build(
                                                $this->getSource(),
                                                $this->getPartner(),
                                                $this->getPartnerConfig(),
                                                $payload);

        $this->addCommission($commission);
    }

    /**
     * @return array
     */
    protected function getCreateCommissionPayload()
    {
        return [
            Entity::FEE      => $this->getCommissionFee(),
            Entity::TAX      => $this->getCommissionTax(),
            Entity::DEBIT    => 0,
            Entity::CREDIT   => $this->getCommissionFee(),
            Entity::CURRENCY => $this->getSource()->getCurrency(),
        ];
    }

    /**
     * @return array
     */
    protected function getMerchantFees(): array
    {
        $pricingFee = new Pricing\Fee;

        return $pricingFee->calculateMerchantFees($this->getSource());
    }

    /**
     * Partner pricing refers to the base pricing at which Razorpay expects the payments from the partners'
     * sub-merchants. Anything additional, goes as a commission to the partner.
     *
     * Eg: When the partner pricing is 1.8%, and,
     *     Sub-merchant A's pricing is 2%   => Commission = 0.2%
     *     Sub-merchant B's pricing is 2.5% => Commission = 0.7%
     *
     * Here, the partner pricing is fixed, and the merchant pricing depends on the rate at which the partner resells.
     * Hence, this is variable commission.
     *
     * @return array
     */
    protected function getPartnerPricing(): array
    {
        $calculator = $this->getFeeCalculator();

        $pricingPlanId = $this->getPartnerConfig()->getImplicitPricingPlanId();

        $pricing = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($pricingPlanId);

        $pricing = $this->addFallbackPricingRulesForCommissions($pricing);

        return $calculator->calculate($pricing);
    }

    /**
     * Fetches the implicit pricing plan id defined in the partner configs and sets the implicitPricingPlan property.
     * The property is set to null if the partner config is set to null or if the implicit pricing plan is not defined.
     */
    protected function setImplicitPricingPlanContext()
    {
        $pricingPlan = $this->partnerConfigCore->getImplicitPlanFromConfig($this->getPartnerConfig());

        if ($pricingPlan === null)
        {
            return;
        }

        $this->setImplicitPricingPlan($pricingPlan);
    }

    protected function setPartnerAppContext()
    {
        $sourceEntity = $this->getSource(); // payment, refund, etc

        $submerchant = $this->getSubMerchant();

        $partnerApp = (new EntityOrigin\Core)->getPartnerAppFromEntityOrigin($sourceEntity, $submerchant);

        if ($partnerApp === null)
        {
            $this->traceContext(TraceCode::COMMISSION_PARTNER_APP_DOES_NOT_EXIST);

            return;
        }

        $this->setPartnerApp($partnerApp);
    }

    /**
     * Fetch and set the partner merchant's details using the partner application
     */
    protected function setPartnerContext()
    {
        $partnerApp = $this->getPartnerApp();

        if ($partnerApp === null)
        {
            return;
        }

        $partner = (new Merchant\Core)->getPartnerFromApp($partnerApp);

        if ($partner === null)
        {
            // This should never happen because the partner context is fetched from the database
            $this->traceContext(TraceCode::COMMISSION_PARTNER_DOES_NOT_EXIST, [], Trace::CRITICAL);

            return;
        }

        $this->setPartner($partner);
    }

    /**
     * Fetch the relevant partner config for the application-submerchant mapping.
     * The defined config could be blanket app-level configuration or a submerchant-level overridden configuration.
     * Both the cases are handled internally.
     */
    protected function setPartnerConfigContext()
    {
        if ($this->getPartnerApp() === null)
        {
            return;
        }

        $partnerConfig = $this->partnerConfigCore->fetch($this->getPartnerApp(), $this->getSubMerchant());

        if ($partnerConfig === null)
        {
            // @todo - add logs

            return;
        }

        $this->setPartnerConfig($partnerConfig);
    }

    /**
     * No fallback pricing rules are required for commissions as of now.
     *
     * @todo: Does the calculation break if the required pricing rules are not added or does it ignore assuming 0?
     *
     * @param Plan $pricing
     *
     * @return Plan
     */
    protected function addFallbackPricingRulesForCommissions(Pricing\Plan $pricing)
    {
        return $pricing;
    }

    protected function traceContext(string $traceCode, array $input = [], string $level = Trace::INFO)
    {
        $data = $this->getTraceData();

        $data = array_merge($data, $input);

        $this->trace->addRecord($level, $traceCode, $data);
    }

    protected function getTraceData(): array
    {
        $commissionIds = array_map(
                            function ($commission)
                            {
                                return $commission->getId();
                            },
                            $this->getCommissions());

        return [
            'source_type'    => optional($this->getSource())->getId(),
            'source_id'      => optional($this->getSource())->getEntityName(),
            'submerchant'    => optional($this->getSubMerchant())->getId(),
            'partner'        => optional($this->getPartner())->getId(),
            'partner_app'    => optional($this->getPartnerApp())->getId(),
            'partner_config' => optional($this->getPartnerConfig())->getId(),
            'commissions'    => $commissionIds,
        ];
    }
}
