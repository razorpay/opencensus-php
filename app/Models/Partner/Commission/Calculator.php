<?php

namespace RZP\Models\Partner\Commission;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use Razorpay\OAuth\Application as OAuthApp;

use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\EntityOrigin;
use RZP\Models\Pricing\Plan;
use RZP\Exception\LogicException;
use RZP\Models\Partner\Commission;
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
     * @var CommissionSourceInterface
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
     * @var null
     */
    protected $explicitPricingPlan = null;

    /**
     * @var
     */
    protected $partnerConfigCore;

    /**
     * @var null|FeeCalculator\Base
     */
    protected $feeCalculator = null;

    /**
     * Is set to true if the source txn has been initiated by a partner or a partner associated OAuth application.
     *
     * @var bool
     */
    protected $isPartnerOriginated = false;

    /**
     * Calculator constructor.
     *
     * @param CommissionSourceInterface $sourceEntity
     */
    public function __construct(CommissionSourceInterface $sourceEntity)
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
     * @return CommissionSourceInterface
     */
    public function getSource(): CommissionSourceInterface
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
     * Implicit pricing plan property will be set to null if the partner does not exist.
     *
     * @return Plan|null
     */
    public function getExplicitPricingPlan()
    {
        return $this->explicitPricingPlan;
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

    /**
     * @return bool
     */
    public function isPartnerOriginated(): bool
    {
        return ($this->isPartnerOriginated === true);
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
     * @param CommissionSourceInterface $source
     */
    public function setSource(CommissionSourceInterface $source)
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
     * @param Plan $pricingPlan
     */
    public function setExplicitPricingPlan(Plan $pricingPlan)
    {
        $this->explicitPricingPlan = $pricingPlan;
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

    /**
     * @param bool $isPartnerOriginated
     */
    public function setIsPartnerOriginated(bool $isPartnerOriginated)
    {
        $this->isPartnerOriginated = $isPartnerOriginated;
    }

    // ====================================== END ======================================

    /**
     * @param CommissionSourceInterface $sourceEntity
     */
    protected function setBaseContext(CommissionSourceInterface $sourceEntity)
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

        // configuration's explicit pricing plan
        $this->setExplicitPricingPlanContext();
    }

    /**
     * @return bool
     */
    public function shouldCreateCommission(): bool
    {
        if (Constants::isValidCommissionSource($this->getSource()) === false)
        {
            $this->traceContext(TraceCode::COMMISSION_INVALID_SOURCE_ENTITY, [], Trace::CRITICAL);

            return false;
        }

        if ($this->getPartner() === null)
        {
            // If the partner does not exist, no need to add a log for each source entity (payment/refund/..)
            return false;
        }

        if ($this->isCommissionApplicable() === false)
        {
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
        if ($this->getPartnerConfig() === null)
        {
            $this->traceContext(TraceCode::COMMISSION_NOT_APPLICABLE_CONFIG_NOT_DEFINED);

            return false;
        }

        if ($this->getExplicitPricingPlan() === null)
        {
            // Commissions is not applicable if neither implicit nor explicit plans are defined
            if ($this->getImplicitPricingPlan() === null)
            {
                $this->traceContext(TraceCode::COMMISSION_NOT_APPLICABLE_PLANS_NOT_SET);

                return false;
            }

            $now    = Carbon::now(Timezone::IST)->getTimestamp();
            $expiry = $this->getPartnerConfig()->getImplicitExpiryAt();

            // Commissions is not applicable if implicit plan has expired and explicit plan is not defined
            if ((empty($expiry) === false) and ($expiry < $now))
            {
                $this->traceContext(TraceCode::COMMISSION_NOT_APPLICABLE_IMPLICIT_EXPIRED);

                return false;
            }
        }

        $partnerType = $this->getPartner()->getPartnerType();

        //
        // Block commissions for all payments of an aggregator's submerchant which are not coming through the partner
        // auth. This also applies to banks, pure platforms and fully managed partners.
        //
        if (($this->isPartnerOriginated() === false) and
            (in_array($partnerType, Constants::$partnerTypesEligibleWithoutOrigin, true) === false))
        {
            return false;
        }

        // Blocks create commission if the conditions are not supported, from here -

        if ($this->isCustomerFeeBearer() === true)
        {
            $this->traceContext(TraceCode::COMMISSION_NOT_APPLICABLE_INVALID_FEE_BEARER);

            return false;
        }

        if ($this->getSubMerchant()->isPrepaid() === false)
        {
            $this->traceContext(TraceCode::COMMISSION_NOT_APPLICABLE_INVALID_FEE_MODEL);

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
    protected function calculate()
    {
        $this->buildImplicitVariableCommissionEntities();
    }

    /**
     * Calculates all types of applicable commissions [implicit (fixed and variable), explicit (fixed)] and saves them.
     */
    public function calculateAndSaveCommission()
    {
        // Ensure that this is being called within a database transaction
        assertTrue ($this->repo->commission->isTransactionActive());

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

        $commissions = [];

        foreach ($this->commissions as $commission)
        {
            $this->updateCommissionStatus($commission);

            $commission = $commission->toArrayPublic();

            // merchant relation need not be logged
            unset($commission['merchant']);

            $commissions[] = $commission;

            // @todo: Uncomment once the logs are verified
            // $this->repo->saveOrFail($commission);
        }

        $this->traceContext(TraceCode::COMMISSION_LOGGED, ['commissions' => $commissions]);
    }

    protected function updateCommissionStatus(Commission\Entity $commission)
    {
        //
        // @todo: Add a check. Implicit commissions must be set processed once picked up, and, the status for the
        // explicit commissions must be updated based on the explicit_should_charge flag. Also, add fn description.
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

        try
        {
            list($partnerFee, $partnerTax, $partnerSplit) = $this->getPartnerPricing();
        }
        catch (LogicException $ex)
        {
            if ($ex->getCode() === ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT)
            {
                $this->traceContext(
                    TraceCode::COMMISSION_NOT_DEFINED,
                    [
                        'merchant_fees' => $merchantFee,
                        'merchant_tax'  => $merchantTax,
                    ]);
                return;
            }
            throw $ex;
        }

        $this->setMerchantFee($merchantFee);
        $this->setMerchantTax($merchantTax);
        $this->setPartnerFee($partnerFee);
        $this->setPartnerTax($partnerTax);

        if ($partnerFee === 0)
        {
            // The partner fee computed based on the implicit partner pricing is zero
            $this->traceContext(
                TraceCode::COMMISSION_ZERO_PARTNER_FEES,
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

        $pricingPlanId = $this->getImplicitPricingPlan()->getId();

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

    /**
     * Fetches the explicit pricing plan id defined in the partner configs and sets the explicitPricingPlan property.
     * The property is set to null if the partner config is set to null or if the explicit pricing plan is not defined.
     */
    protected function setExplicitPricingPlanContext()
    {
        $pricingPlan = $this->partnerConfigCore->getExplicitPlanFromConfig($this->getPartnerConfig());

        if ($pricingPlan === null)
        {
            return;
        }

        $this->setExplicitPricingPlan($pricingPlan);
    }

    protected function setPartnerAppContext()
    {
        $sourceEntity = $this->getSource(); // payment, refund, etc

        $submerchant = $this->getSubMerchant();

        $entityOriginCore = new EntityOrigin\Core;

        if ($entityOriginCore->isOriginApplication($sourceEntity) === true)
        {
            // $partnerApp will always be a non-null value here
            $partnerApp = $entityOriginCore->getOrigin($sourceEntity);

            $this->setIsPartnerOriginated(true);
        }
        else
        {
            $partnerApp = (new Merchant\AccessMap\Core)->getNonPurePlatformPartnerApp($submerchant);

            $this->setIsPartnerOriginated(false);
        }

        if ($partnerApp === null)
        {
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

            $traceData = $this->getTraceData();

            throw new LogicException(
                'The partner application does not have an owner merchant',
                null,
                $traceData);
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
