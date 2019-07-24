<?php

namespace RZP\Models\Partner\Config;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Pricing\Plan;
use RZP\Models\Merchant\AccessMap;

use Razorpay\OAuth\Application;

class Core extends Base\Core
{
    /**
     * @param Application\Entity   $application
     * @param array                $input
     * @param Merchant\Entity|null $subMerchant
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function create(Application\Entity $application, array $input, Merchant\Entity $subMerchant = null) : Entity
    {
        $this->validateCreate($application, $input, $subMerchant);

        $config = new Entity;

        $config->build($input);

        if (empty($subMerchant) === true)
        {
            $config->entity()->associate($application);
        }
        else
        {
            $config->entity()->associate($subMerchant);
            $config->origin()->associate($application);
        }

        $this->repo->saveOrFail($config);

        return $config;
    }

    /**
     * @param Application\Entity   $application
     * @param array                $input
     * @param Merchant\Entity|null $subMerchant
     *
     * @throws Exception\BadRequestException
     */
    protected function validateCreate(
        Application\Entity $application,
        array $input,
        Merchant\Entity $subMerchant = null)
    {
        $config = null;

        if (empty($subMerchant) === true)
        {
            $config = $this->repo->partner_config->getApplicationConfig($application->getId());
        }
        else
        {
            (new AccessMap\Core)->validateMerchantMappedToApplication($subMerchant, $application);

            $config = $this->repo->partner_config->getSubMerchantConfig($application->getId(), $subMerchant->getId());
        }

        $configValidator = new Validator;

        // check if application/subMerchant already has config defined
        $configValidator->validateEmptyConfig($config);

        $this->validatePricingPlans($input);

        (new Validator)->validateSettleToPartner($application, $input, $subMerchant);
    }

    /**
     * If submerchant config is present, returns it. Else returns associated partners config
     * 
     * @param Application\Entity   $application
     * @param Merchant\Entity|null $subMerchant
     *
     * @return null|Entity
     * @throws Exception\BadRequestException
     */
    public function fetch(Application\Entity $application, Merchant\Entity $subMerchant = null)
    {
        $config = null;

        if (empty($subMerchant) === false)
        {
            (new AccessMap\Core)->validateMerchantMappedToApplication($subMerchant, $application);

            $config = $this->repo->partner_config->getSubMerchantConfig($application->getId(), $subMerchant->getId());
        }

        if (empty($config) === true)
        {
            $config = $this->repo->partner_config->getApplicationConfig($application->getId());
        }

        return $config;
    }

    /**
     * Fetch all default and overridden configs of an application
     *
     * @param Application\Entity $application
     *
     * @return mixed
     */
    public function fetchAllConfigForApp(Application\Entity $application)
    {
        $appIds = [$application->getId()];

        return $this->repo->partner_config->fetchAllConfigForApps($appIds);
    }

    /**
     * @param string $id
     * @param array  $input
     *
     * @return Entity
     */
    public function edit(string $id, array $input) : Entity
    {
        $this->validatePricingPlans($input);

        $config = $this->repo->partner_config->findOrFailPublic($id);

        list($application, $submerchant) = $this->getEntitiesFromConfig($config);

        $config->edit($input, 'edit');

        (new Validator)->validateSettleToPartner($application, $input, $submerchant);

        $this->repo->saveOrFail($config);

        return $config;
    }

    /**
     * @param Entity|null $partnerConfig
     *
     * @return Plan|null
     */
    public function getImplicitPlanFromConfig($partnerConfig)
    {
        if ($partnerConfig === null)
        {
            return null;
        }

        $pricingPlanId = $partnerConfig->getImplicitPricingPlanId();

        if ($pricingPlanId === null)
        {
            return null;
        }

        $pricingPlan = $this->repo->pricing->getPlan($pricingPlanId);

        if (empty($pricingPlan) === true)
        {
            return null;
        }

        return $pricingPlan;
    }

    /**
     * @param Entity|null $partnerConfig
     *
     * @return Plan|null
     */
    public function getExplicitPlanFromConfig($partnerConfig)
    {
        if ($partnerConfig === null)
        {
            return null;
        }

        $pricingPlanId = $partnerConfig->getExplicitPricingPlanId();

        if ($pricingPlanId === null)
        {
            return null;
        }

        $pricingPlan = $this->repo->pricing->getPlan($pricingPlanId);

        return $pricingPlan;
    }

    public function fetchAllConfigsByPartner(Merchant\Entity $merchant)
    {
        if ($merchant->isPartner() === false)
        {
            return new Base\PublicCollection;
        }

        // if the merchant is a partner, there will be at least one partner app
        $appType         = $merchant->isPurePlatformPartner() ? null : Application\Type::PARTNER;

        $applications    = (new Application\Repository)
                                    ->findActiveApplicationsByMerchantIdAndType($merchant->getId(), $appType);

        $appIds = $applications->getIds();

        return $this->repo->partner_config->fetchAllConfigForApps($appIds);
    }

    public function fetchAllEnabledConfigGroupsByPartner(Merchant\Entity $merchant)
    {
        $configs = $this->fetchAllConfigsByPartner($merchant);

        $configs = $configs->filter(function ($config) {
            return ($config->isCommissionsEnabled() === true);
        });

        return $this->groupConfigsByModel($configs);
    }

    public function fetchAllConfigGroupsByPartner(Merchant\Entity $merchant)
    {
        $configs = $this->fetchAllConfigsByPartner($merchant);

        return $this->groupConfigsByModel($configs);
    }

    /**
     * Group configs by commission model type
     *
     * @param Base\PublicCollection $configs
     *
     * @return array
     */
    protected function groupConfigsByModel(Base\PublicCollection $configs)
    {
        $commissionConfigs = $configs->filter(function ($config) {
            return ($config->getCommissionModel() === CommissionModel::COMMISSION);
        });

        $subventionConfigs = $configs->filter(function ($config) {
            return ($config->getCommissionModel() === CommissionModel::SUBVENTION);
        });

        return [$commissionConfigs, $subventionConfigs];
    }

    protected function validatePricingPlans(array $input)
    {
        // check if all plan ids are valid
        if (empty($input[Entity::DEFAULT_PLAN_ID]) === false)
        {
            $this->repo->pricing->getPricingPlanByIdOrFailPublic($input[Entity::DEFAULT_PLAN_ID]);
        }

        if (empty($input[Entity::IMPLICIT_PLAN_ID]) === false)
        {
            $this->repo->pricing->getPlanByIdOrFailPublic($input[Entity::IMPLICIT_PLAN_ID]);
        }

        if (empty($input[Entity::EXPLICIT_PLAN_ID]) === false)
        {
            $this->repo->pricing->getCommissionPlanById($input[Entity::EXPLICIT_PLAN_ID], true, true);
        }
    }

    /**
     * Returns submerchant and application entity from the Partner config entity
     *
     * @param Entity $config
     *
     * @return array
     */
    protected function getEntitiesFromConfig(Entity $config): array
    {
        $subMerchant     = null;
        $application     = null;
        $applicationRepo = new Application\Repository;

        $entityType = $config->getEntityType();
        $entityId   = $config->getEntityId();

        switch ($entityType)
        {
            case Constants::APPLICATION:
                $application = $applicationRepo->findOrFail($entityId);
                break;

            case Constants::MERCHANT:
                $originId    = $config->getOriginId();
                $application = $applicationRepo->findOrFail($originId);
                $subMerchant = $this->repo->merchant->findOrFail($entityId);
                break;
        }

        return [$application, $subMerchant];
    }
}
