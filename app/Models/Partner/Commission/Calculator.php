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
use RZP\Constants as BaseConstants;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Pricing\Calculator as FeeCalculator;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

/**
 * Class Calculator
 *
 * In the case of variable commission
 * Partner pricing refers to the base pricing at which Razorpay expects the payments from the partners'
 * sub-merchants. Anything additional, goes as a commission to the partner.
 *
 * Eg: When the partner pricing is 1.8%, and,
 *     Sub-merchant A's pricing is 2%   => Commission = 0.2%
 *     Sub-merchant B's pricing is 2.5% => Commission = 0.7%
 *
 * Here, the partner pricing goes into RZP ledger and commission goes to partner
 *
 * In the case of fixed commission
 * Partner pricing refers to commission the partner will get from the sub-merchant transaction.
 *
 * Ex: When the partner pricing is 0.2% and
 * sub-merchant A's pricing is 2% => commission is 0.2% and RZP gets 1.8%
 * sub-merchant A's pricing is 2.5% => commission is 0.2% and RZP gets 2.3%
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
     * Tax components - [IGST => 1800] or [CGST => 900, SGST => 900]
     *
     * @var array
     */
    protected $taxComponents = [];

    /**
     * Calculator constructor.
     *
     * @param CommissionSourceInterface $sourceEntity
     *
     * @throws LogicException
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

    public function getTaxComponents(): array
    {
        return $this->taxComponents;
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

    /**
     * @param int $partnerTax
     */
    public function setTaxComponents(array $taxComponents)
    {
        $this->taxComponents = $taxComponents;
    }

    // ====================================== END ======================================

    protected function isImplicitCommissionFixed(): bool
    {
        $type = optional($this->getImplicitPricingPlan())->getType();

        return ($type === Pricing\Type::COMMISSION);
    }

    protected function isImplicitCommissionVariable(): bool
    {
        $type = optional($this->getImplicitPricingPlan())->getType();

        return ($type === Pricing\Type::PRICING);
    }

    /**
     * @param CommissionSourceInterface $sourceEntity
     *
     * @throws LogicException
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

        // set tax components based on partner merchant's account details
        $this->setTaxComponentsContext();

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

    protected function buildCommission(array $payload)
    {
        $commission = (new Commission\Core)->build(
            $this->getSource(),
            $this->getPartner(),
            $this->getPartnerConfig(),
            $payload);

        $this->updateCommissionStatus($commission);

        return $commission;
    }

    protected function addCommission(Entity $commission)
    {
        $this->commissions[] = $commission;
    }

    /**
     * Calculates all types of applicable commissions [implicit (fixed and variable), explicit (fixed)]
     * and updates the class property - $this->commissions.
     */
    protected function calculate()
    {
        $this->setMerchantFees();

        $this->buildImplicitVariableCommissionEntities();

        $this->buildImplicitFixedCommissionEntities();
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

        foreach ($this->commissions as $commission)
        {
            // @todo remove once logs are verified
            if ($commission->getType() === Type::IMPLICIT)
            {
                if (($this->isImplicitCommissionVariable() === true) or ($this->isImplicitCommissionFixed() === true))
                {
                    $commissionData = $commission->toArrayPublic();

                    // merchant relation need not be logged
                    unset($commissionData['merchant']);

                    $this->traceContext(TraceCode::COMMISSION_LOGGED, ['commissions' => $commissionData]);

                    continue;
                }
            }
            else
            {
                continue;
            }

            $this->repo->saveOrFail($commission);
        }
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
     * For fixed commissions, this will use the fixed commission pricing to calculate the commissions.
     *
     * @return null
     * @throws LogicException
     */
    protected function buildImplicitFixedCommissionEntities()
    {
        if ($this->isImplicitCommissionFixed() === false)
        {
            return;
        }

        list($commissionFee, $commissionTax) = $this->getFeesByPlan($this->getImplicitPricingPlan());

        list($commissionFee, $commissionTax) = $this->addTaxToCommissionIfApplicable($commissionFee, $commissionTax);

        $isCommissionFeeValid = $this->isImplicitCommissionValid($commissionFee, $commissionTax);

        if ($isCommissionFeeValid === false)
        {
            return;
        }

        $payload = [
            Entity::FEE      => $commissionFee,
            Entity::TAX      => $commissionTax,
            Entity::TYPE     => Type::IMPLICIT,
            Entity::DEBIT    => 0,
            Entity::CREDIT   => $commissionFee,
            Entity::CURRENCY => $this->getSource()->getCurrency(),
        ];

        $commission = $this->buildCommission($payload);

        $this->addCommission($commission);
    }

    /**
     * For variable commissions, this will calculate the difference b/w the merchant pricing and the partner pricing.
     *
     * Once calculated, the commission entities will be added to the class property - $this->commissions
     *
     * @return null
     * @throws LogicException
     */
    protected function buildImplicitVariableCommissionEntities()
    {
        if ($this->isImplicitCommissionVariable() === false)
        {
            return;
        }

        $merchantFee = $this->getMerchantFee();
        $merchantTax = $this->getMerchantTax();

        list($partnerFee, $partnerTax) = $this->getFeesByPlan($this->getImplicitPricingPlan());

        $this->setPartnerFee($partnerFee);
        $this->setPartnerTax($partnerTax);

        $commissionFee = $merchantFee - $partnerFee;
        $commissionTax = $merchantTax - $partnerTax;

        list($commissionFee, $commissionTax) = $this->addTaxToCommissionIfApplicable($commissionFee, $commissionTax);

        $isCommissionFeeValid = $this->isImplicitCommissionValid($commissionFee, $commissionTax);

        if ($isCommissionFeeValid === false)
        {
            return;
        }

        $payload = [
            Entity::FEE      => $commissionFee,
            Entity::TAX      => $commissionTax,
            Entity::TYPE     => Type::IMPLICIT,
            Entity::DEBIT    => 0,
            Entity::CREDIT   => $commissionFee,
            Entity::CURRENCY => $this->getSource()->getCurrency(),
        ];

        $commission = $this->buildCommission($payload);

        $this->addCommission($commission);
    }

    protected function getTracePayloadData(int $commissionFee, int $commissionTax, string $type): array
    {
        $tracePayLoad = [
            'commission_fees' => $commissionFee,
            'commission_tax'  => $commissionTax,
        ];

        if ($type === Type::IMPLICIT)
        {
            $tracePayLoad['merchant_fees'] = $this->getMerchantFee();
            $tracePayLoad['merchant_tax']  = $this->getMerchantTax();

            if ($this->isImplicitCommissionVariable() === true)
            {
                $tracePayLoad['partner_fees'] = $this->getPartnerFee();
                $tracePayLoad['partner_tax']  = $this->getPartnerTax();
            }
        }

        return $tracePayLoad;
    }

    protected function isImplicitCommissionValid(int $commissionFee, int $commissionTax): bool
    {
        $tracePayLoad = $this->getTracePayloadData($commissionFee, $commissionTax, Type::IMPLICIT);

        $isValid = $this->isCommissionFeeValid($commissionFee, $tracePayLoad);

        if ($isValid === false)
        {
            return false;
        }

        if (($this->isImplicitCommissionVariable() === true) and ($this->getPartnerFee() === 0))
        {
            $this->traceContext(TraceCode::COMMISSION_ZERO_PARTNER_FEES, $tracePayLoad);

            return false;
        }

        if ($commissionFee > $this->getMerchantFee())
        {
            $this->traceContext(TraceCode::COMMISSION_COMPUTED_GREATER_THAN_MERCHANT_FEE, $tracePayLoad);

            return false;
        }

        return true;
    }

    protected function isCommissionFeeValid(int $commissionFee, array $tracePayLoad): bool
    {
        if ($commissionFee < 0)
        {
            $this->traceContext(TraceCode::COMMISSION_COMPUTED_NEGATIVE, $tracePayLoad,Trace::CRITICAL);

            return false;
        }

        if ($commissionFee === 0)
        {
            $this->traceContext(TraceCode::COMMISSION_COMPUTED_ZERO, $tracePayLoad);

            return false;
        }

        return true;
    }

    /**
     * @param Plan $pricing
     *
     * @return array
     * @throws LogicException
     */
    protected function getFeesByPlan(Plan $pricing): array
    {
        $calculator = $this->getFeeCalculator();

        $pricing = $this->addFallbackRulesForCommissions($pricing);

        // initialize - [total fee, total tax, feeSplit]
        $feeDetails = [0, 0, new Base\PublicCollection];

        try
        {
            $feeDetails = $calculator->calculate($pricing);
        }
        catch (LogicException $ex)
        {
            // If the exception is because a relevant pricing rule is not defined, do not block; assume zero.

            if ($ex->getCode() === ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT)
            {
                $this->traceContext(TraceCode::COMMISSION_NOT_DEFINED);

                return $feeDetails;
            }

            throw $ex;
        }

        return $feeDetails;
    }

    protected function setMerchantFees()
    {
        $pricingFee = new Pricing\Fee;

        list($merchantFee, $merchantTax) = $pricingFee->calculateMerchantFees($this->getSource());

        $this->setMerchantFee($merchantFee);
        $this->setMerchantTax($merchantTax);
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
     *
     * @throws LogicException
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

    protected function setTaxComponentsContext()
    {
        if ($this->getPartner() === null)
        {
            return;
        }

        $taxComponents = FeeCalculator\Base::getTaxComponents($this->getPartner());

        $this->setTaxComponents($taxComponents);
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
     * @param Plan $pricing
     *
     * @return Plan
     */
    protected function addFallbackRulesForCommissions(Pricing\Plan $pricing)
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
            'source_type'       => optional($this->getSource())->getEntityName(),
            'source_id'         => optional($this->getSource())->getId(),
            'submerchant_id'    => optional($this->getSubMerchant())->getId(),
            'partner_id'        => optional($this->getPartner())->getId(),
            'partner_app_id'    => optional($this->getPartnerApp())->getId(),
            'partner_config_id' => optional($this->getPartnerConfig())->getId(),
            'commission_ids'    => $commissionIds,
        ];
    }

    /**
     * As per RBI guidelines, for card payments < 2K INR, no GST is charged.
     * The commission calculator internally uses the merchant fee calculator to calculate commission.
     * Hence for card payments < 2K INR, the commission calculated will always have zero tax.
     *
     * This function adds tax to the commission calculation if the tax calculated so far is zero.
     *
     * Example:
     *  For payment amount = 1000 * 100 and variable commission with partner pricing as 1.8% & merchant pricing as 2%,
     *  Partner fees and tax                                        = 1800, 0
     *  Merchant fees and tax                                       = 2000, 0
     *  Commission fees and tax as per calculation (difference)     = 200 , 0
     *  Commission fees and tax after adding commission explicitly  = 236 , 36
     *
     * @param int $commissionFee
     * @param int $commissionTax
     *
     * @return array
     * @throws LogicException
     */
    protected function addTaxToCommissionIfApplicable(int $commissionFee, int $commissionTax): array
    {
        if ($commissionTax !== 0)
        {
            return [$commissionFee, $commissionTax];
        }

        $entityType = $this->getSource()->getEntity();

        switch ($entityType)
        {
            case BaseConstants\Entity::PAYMENT:

                return $this->addTaxToCommissionForPayment($commissionFee);

            default:

                $traceData = $this->getTraceData();

                throw new LogicException(
                    'The GST calculation on commission for ' . $entityType . ' is not handled',
                    null,
                    $traceData);
        }
    }

    /**
     * This function calculates GST over the commission fee for a payment, and returns the commission fee and tax.
     *
     * @param int $commissionFee
     *
     * @return array
     */
    protected function addTaxToCommissionForPayment(int $commissionFee): array
    {
        $taxValue   = 0;
        $totalTaxes = 0;

        $taxComponents = $this->getTaxComponents();

        foreach ($taxComponents as $name => $percentage)
        {
            if (in_array($name, [FeeBreakupName::CGST, FeeBreakupName::SGST], true) === true)
            {
                $taxValue = ((int) round(($percentage * $commissionFee) / 10000));
            }
            else if ($name === FeeBreakupName::IGST)
            {
                // Calculate as per cgst percentage, and double it to get the exact tax value.
                // We do this so that if this value needs to be split later into sgst+cgst, it is an even value
                $calculationPercentage = FeeCalculator\Base::CGST_PERCENTAGE;

                $taxValue = 2 * ((int) round(($calculationPercentage * $commissionFee) / 10000));
            }

            $totalTaxes += $taxValue;
        }

        $commissionTax = $totalTaxes;

        $commissionFee += $commissionTax;

        return [$commissionFee, $commissionTax];
    }
}
