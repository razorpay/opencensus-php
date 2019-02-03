<?php

namespace RZP\Models\Partner\Commission;

use App;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;

use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\EntityOrigin;
use RZP\Models\Pricing\Plan;
use RZP\Base\RepositoryManager;
use RZP\Exception\LogicException;
use RZP\Models\Partner\Commission;
use Razorpay\OAuth\Application as OAuthApp;
use RZP\Models\Partner\Config as PartnerConfig;

/**
 * Class Calculator
 *
 * @package RZP\Models\Partner\Commission
 */
class Calculator
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    /**
     * Environment - production/testing/beta
     *
     * @var String
     */
    protected $env;

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
     * Calculator constructor.
     *
     * @param Base\PublicEntity $sourceEntity
     */
    public function __construct(Base\PublicEntity $sourceEntity)
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->env = $this->app['env'];

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        if (Constants::isValidCommissionSource($sourceEntity) === false)
        {
            return;
        }

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
    public function getSource()
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
     * @return PartnerConfig\Entity|null
     */
    public function getPartnerConfig()
    {
        return $this->partnerConfig;
    }

    /**
     * @return OAuthApp\Entity|null
     */
    public function getPartnerApp()
    {
        return $this->partnerApp;
    }

    /**
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

    public function setCommissionFee(int $fee)
    {
        $this->commissionFee = $fee;
    }

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
    public function shouldCreateCommission(): bool
    {
        if (Constants::isValidCommissionSource($this->getSource()) === false)
        {
            $this->traceContext(TraceCode::COMMISSION_INVALID_SOURCE_ENTITY);

            return false;
        }

        if ($this->isCommissionApplicable() === false)
        {
            $this->traceContext(TraceCode::COMMISSION_NOT_APPLICABLE);

            return false;
        }

        if ($this->isCommissionEnabled() === false)
        {
            $this->traceContext(TraceCode::COMMISSION_NOT_ENABLED);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isCommissionApplicable(): bool
    {
        if ($this->getPartner() === null)
        {
            $this->traceContext(TraceCode::COMMISSION_PARTNER_DOES_NOT_EXIST);

            return false;
        }

        if ($this->getPartnerConfig() === null)
        {
            $this->traceContext(TraceCode::COMMISSION_PARTNER_CONFIG_NOT_DEFINED);

            return false;
        }

        // Blocks create commission if the conditions are not supported, from here -

        if ($this->getImplicitPricingPlan()->isTypePricing() === false)
        {
            $this->traceContext(TraceCode::COMMISSION_PARTNER_PRICING_TYPE_NOT_SUPPORTED);

            return false;
        }

        if ($this->isCustomerFeeBearer() === true)
        {
            $this->traceContext(TraceCode::COMMISSION_CUSTOMER_FEE_BEARER_NOT_SUPPORTED);

            return false;
        }

        if ($this->getSubMerchant()->isPrepaid() === false)
        {
            $this->traceContext(TraceCode::COMMISSION_CUSTOMER_FEE_MODEL_NOT_SUPPORTED);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isCustomerFeeBearer(): bool
    {
        // Eg: 'payment', 'refund'
        $sourceEntityName = $this->getSource()->getEntity();

        $submerchant = $this->getSubMerchant();

        return ((Pricing\Feature::isCustomerFeeBearerSupported($sourceEntityName) === true) and
                    ($submerchant->isFeeBearerCustomer() === true));
    }

    /**
     * @return bool
     */
    public function isCommissionEnabled(): bool
    {
        return ($this->getPartnerConfig()->isCommissionEnabled() === true);
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
     * Saves the list of commission entities built so far
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

    protected function saveCommission()
    {
        foreach ($this->commissions as $commission)
        {
            $this->repo->saveOrFail($commission);
        }

        $this->traceContext(TraceCode::COMMISSION_CREATED);
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

        if ($commissionFee == 0)
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

        $this->setCommissionFee($commissionFee);

        $this->setCommissionTax($commissionTax);

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
            Entity::STATUS   => Status::CREATED,
            Entity::CURRENCY => $this->getSource()->getCurrency(),
        ];
    }

    /**
     * @return array
     */
    protected function getMerchantFees()
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
    protected function getPartnerPricing()
    {
        $pricingFee = new Pricing\Fee;

        // primary or banking
        $product = $pricingFee->getProductForEntity($this->getSource());

        $calculator = new Pricing\FeeCalculator($this->getSource(), $product);

        $pricingPlanId = $this->getPartnerConfig()->getImplicitPricingPlanId();

        $pricing = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($pricingPlanId);

        $pricing = $this->addFallbackPricingRulesForCommissions($pricing);

        return $calculator->calculate($pricing);
    }

    protected function setImplicitPricingPlanContext()
    {
        if ($this->getPartnerConfig() === null)
        {
            return;
        }

        $partnerConfig = $this->getPartnerConfig();

        $pricingPlanId = $partnerConfig->getImplicitPricingPlanId();

        $pricingPlan = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($pricingPlanId);

        $this->setImplicitPricingPlan($pricingPlan);
    }

    protected function setPartnerAppContext()
    {
        $origin = $originType = $originId = $partnerApp = null;

        $entityOrigin = $this->getSource()->entityOrigin;

        //
        // If the origin is defined for the source entity, fetch the origin and if
        // an application had initiated the source entity (payment, refund etc) then
        // fetch the partner configurations defined for the application-submerchant.
        //
        if ($entityOrigin !== null)
        {
            $origin = $entityOrigin->origin;

            $originType = $origin->getEntityName();
        }

        // @todo add comments
        if (($entityOrigin === null) or ($originType == EntityOrigin\Constants::MERCHANT))
        {
            $accessMap = $this->repo->merchant_access_map->getPartnerApplication($this->subMerchant->getId());

            if($accessMap !== null)
            {
                $partnerApp = $accessMap->entity;
            }
        }
        else if ($originType == EntityOrigin\Constants::APPLICATION)
        {
            $partnerApp = $origin;
        }

        if ($partnerApp !== null)
        {
            $this->setPartnerApp($partnerApp);
        }
        else
        {
            // no partner. @todo add logs

            return;
        }
    }

    protected function setPartnerContext()
    {
        if ($this->getPartnerApp() === null)
        {
            return;
        }

        $partnerApp = $this->getPartnerApp();

        $partnerId = $partnerApp->getMerchantId();

        $partner = $this->repo->merchant->find($partnerId);

        if ($partner === null)
        {
            // This should never happen because the partner context is fetched from the database
            $this->traceContext(TraceCode::COMMISSION_PARTNER_DOES_NOT_EXIST, [], Trace::CRITICAL);

            return;
        }

        $this->setPartner($partner);
    }

    protected function setPartnerConfigContext()
    {
        if ($this->getPartnerApp() === null)
        {
            return;
        }

        $partnerApp = $this->getPartnerApp();

        $partnerConfig = (new PartnerConfig\Core)->fetch($partnerApp, $this->getSubMerchant());

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
    public function addFallbackPricingRulesForCommissions(Pricing\Plan $pricing)
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
            'source_type'    => optional($this->getSource())->getId()         ?? null,
            'source_id'      => optional($this->getSource())->getEntityName() ?? null,
            'submerchant'    => optional($this->getSubMerchant())->getId()    ?? null,
            'partner'        => optional($this->getPartner())->getId()        ?? null,
            'partner_app'    => optional($this->getPartnerApp())->getId()     ?? null,
            'partner_config' => optional($this->getPartnerConfig())->getId()  ?? null,
            'commissions'    => $commissionIds,
        ];
    }
}
