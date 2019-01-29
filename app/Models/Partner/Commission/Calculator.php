<?php

namespace RZP\Models\Partner\Commission;

use App;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Pricing\Plan;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\EntityOrigin;

use RZP\Base\RepositoryManager;


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
    protected $commissionFee;

    /**
     * @var int
     */
    protected $commissionTax;

    protected $commissions = [];

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

        // required to check if the commission entity must be created (shouldCreateCommission function)
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

    public function getPartnerApp()
    {
        return $this->partnerApp;
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

    public function setPartnerApp($partnerApp)
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

    public function setImplicitPricingPlan(Plan $pricingPlan)
    {
        $this->implicitPricingPlan = $pricingPlan;
    }

    // ====================================== END ======================================

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

    public function shouldCreateCommission(): bool
    {
        if (Constants::isValidCommissionSource($this->getSource()) === false)
        {
            $sourceEntity = $this->getSource()->getEntity();

            $this->trace->info(
                TraceCode::COMMISSION_INVALID_SOURCE_ENTITY,
                ['entity' => $sourceEntity]);

            return false;
        }

        if ($this->isCommissionApplicable() === false)
        {
            // @todo: add a log for each one here

            $this->trace->info(TraceCode::COMMISSION_NOT_APPLICABLE);

            return false;
        }

        if ($this->isCommissionEnabled() === false)
        {
            $this->trace->info(TraceCode::COMMISSION_NOT_ENABLED);

            return false;
        }

        if ($this->isCommissionDefined() === false)
        {
            $this->trace->info(TraceCode::COMMISSION_NOT_DEFINED);

            return false;
        }

        return true;
    }

    public function isCommissionApplicable(): bool
    {
        if ($this->partner === null)
        {
            $this->trace->info(TraceCode::COMMISSION_PARTNER_DOES_NOT_EXIST);

            return false;
        }

        if ($this->partnerConfig === null)
        {
            $this->trace->info(TraceCode::COMMISSION_PARTNER_CONFIG_NOT_DEFINED);

            return false;
        }

        // Blocks create commission if the conditions are not supported, from here -

        if ($this->implicitPricingPlan->isTypePricing() === false)
        {
            $this->trace->info(TraceCode::COMMISSION_PARTNER_PRICING_TYPE_NOT_SUPPORTED);

            return false;
        }

        if ($this->isCustomerFeeBearer() === true)
        {
            $this->trace->info(TraceCode::COMMISSION_CUSTOMER_FEE_BEARER_NOT_SUPPORTED);

            return false;
        }

        if ($this->getSubMerchant()->isPrepaid() === false)
        {
            // @todo - add data for traces
            $this->trace->info(TraceCode::COMMISSION_CUSTOMER_FEE_MODEL_NOT_SUPPORTED);

            return false;
        }

        return true;
    }

    protected function isCustomerFeeBearer(): bool
    {
        // Eg: 'payment', 'refund'
        $sourceEntityName = $this->getSource()->getEntity();

        $submerchant = $this->getSubMerchant();

        return ((Pricing\Feature::isCustomerFeeBearerSupported($sourceEntityName) === true) and
                    ($submerchant->isFeeBearerCustomer() === true));
    }

    public function isCommissionEnabled(): bool
    {
        return ($this->getPartnerConfig()->isCommissionEnabled() === true);
    }

    public function isCommissionDefined(): bool
    {
        // @todo
        return true;
    }

    public function calculate()
    {
        $this->getCommissionSplit();

        return;

        //        list($merchantFee, $merchantTax, $merchantFeesSplit) = $this->getMerchantFees();
    }

    public function saveCommission()
    {
        foreach ($this->commissions as $commission)
        {
            $this->repo->saveOrFail($commission);
        }
    }

    protected function getCommissionSplit()
    {
        if ($this->implicitPricingPlan->isTypePricing() === false)
        {
            throw new LogicException("Commission type pricing not yet supported");
        }

        list($merchantFee, $merchantTax, $merchantFeesSplit) = $this->getMerchantFees();

        list($partnerFee, $partnerTax, $partnerSplit) = $this->getPartnerPricing();

        $commissionAmount = $merchantFee - $partnerFee;

        $commissionTax = $merchantTax - $partnerTax;

        if ($commissionAmount < 0)
        {
            $this->trace->info(TraceCode::COMMISSION_COMPUTED_NEGATIVE, [
                'merchant_fees' => $merchantFee,
                'partner_fees'  => $partnerFee,
                'merchant_tax'  => $merchantTax,
                'partner_tax'   => $partnerTax,
            ]);

            return null;
        }

        if ($commissionAmount == 0)
        {
            $this->trace->info(TraceCode::COMMISSION_COMPUTED_ZERO, [
                'merchant_fees' => $merchantFee,
                'partner_fees'  => $partnerFee,
                'merchant_tax'  => $merchantTax,
                'partner_tax'   => $partnerTax,
            ]);

            return null;
        }

        $this->trace->info(TraceCode::COMMISSION_COMPUTED, [
            'merchant_fees'     => $merchantFee,
            'partner_fees'      => $partnerFee,
            'merchant_tax'      => $merchantTax,
            'partner_tax'       => $partnerTax,
            'commission_amount' => $commissionAmount,
            'commission_tax'    => $commissionTax,
        ]);

        // @todo: Add comments here
        $this->commissionFee = $commissionAmount;

        $this->commissionTax = $commissionTax - $merchantTax;

        $payload = $this->getCreateCommissionPayload();

        $commission = (new Commission\Core)->build($this->getSource(), $this->partner, $this->partnerConfig, $payload);

        array_push($this->commissions, $commission);
    }

    protected function getCreateCommissionPayload()
    {
        return [
            Entity::FEE      => $this->commissionFee,
            Entity::TAX      => $this->commissionTax,
            Entity::DEBIT    => 0,
            Entity::CREDIT   => $this->commissionFee,
            Entity::STATUS   => Status::CREATED,
            Entity::CURRENCY => $this->getSource()->getCurrency(),
        ];
    }

    protected function getPartnerPricing()
    {
        $pricingFee = new Pricing\Fee;

        $product = $pricingFee->getProductForEntity($this->getSource());

        $calculator = new Pricing\FeeCalculator($this->getSource(), $product);

        $pricingPlanId = $this->partnerConfig->getImplicitPricingPlanId();

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

    protected function getMerchantFees()
    {
        $pricingFee = new Pricing\Fee;

        return $pricingFee->calculateMerchantFees($this->getSource());
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
            $partnerApp = $this->repo->merchant_access_map->getPartnerApplication($this->subMerchant);
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
            // should not happen. @todo add a critical log
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

    public function addFallbackPricingRulesForCommissions(Pricing\Plan $pricing)
    {
        return $pricing;
    }
}
