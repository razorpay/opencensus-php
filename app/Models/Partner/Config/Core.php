<?php

namespace RZP\Models\Partner\Config;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Pricing\Plan;
use Razorpay\OAuth\Application;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Merchant\MerchantApplications;

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
        $partner = (new Merchant\Core)->getPartnerFromApp($application);

        $this->validateCreate($partner, $application, $input, $subMerchant);

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

        $this->trace->info(
            TraceCode::PARTNER_CONFIG_CREATED,
            [
                'input' => $input,
                'id'    => $config->getId(),
            ]);

        return $config;
    }

    /**
     * @param Merchant\Entity      $partner
     * @param Application\Entity   $application
     * @param array                $input
     * @param Merchant\Entity|null $subMerchant
     *
     * @throws Exception\BadRequestException
     */
    protected function validateCreate(
        Merchant\Entity $partner,
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

        (new Validator)->validateSettleToPartner($partner, $input, $subMerchant);

        (new Validator)->validatePaymentMethodsForPartnerType($partner, $input);
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
     * @param array $input
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function edit(string $id, array $input) : Entity
    {
        $this->validatePricingPlans($input);

        $config = $this->repo->partner_config->findOrFailPublic($id);

        $this->buildPartnerMetadata($config, $input);

        list($application, $submerchant) = $this->getEntitiesFromConfig($config);

        $config->edit($input, 'edit');

        $partner = (new Merchant\Core)->getPartnerFromApp($application);

        (new Validator)->validateSettleToPartner($partner, $input, $submerchant);

        (new Validator)->validatePaymentMethodsForPartnerType($partner, $input);

        $this->repo->saveOrFail($config);

        $this->trace->info(
            TraceCode::PARTNER_CONFIG_EDITED,
            [
                'input' => $input,
                'id'    => $config->getId(),
            ]);

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

        $pricingPlan = $this->repo->pricing->getPlan($pricingPlanId, skipOrgCheck: true);

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

        $pricingPlan = $this->repo->pricing->getPlan($pricingPlanId, skipOrgCheck: true);

        return $pricingPlan;
    }

    public function fetchAllConfigsByPartner(Merchant\Entity $merchant)
    {
        if ($merchant->isPartner() === false)
        {
            return new Base\PublicCollection;
        }

        $appIds = (new Merchant\Core)->getPartnerApplicationIds($merchant);

        return $this->repo->partner_config->fetchAllConfigForApps($appIds);
    }

    public function fetchAllDefaultConfigsByPartner(Merchant\Entity $partner): Base\PublicCollection
    {
        $configs = $this->fetchAllConfigsByPartner($partner);

        return $configs->filter(function ($config) {
            return ($config->isDefaultConfig() === true);
        });
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
            $this->repo->pricing->getPlanByIdOrFailPublic($input[Entity::IMPLICIT_PLAN_ID], skipOrgCheck: true);
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

    /**
     * This function updates the submerchant partner configs associated with provided appIds
     *
     * @param Merchant\Entity $merchant  // Submerchant Entity
     * @param array           $appIds    // Submerchant linked application ids
     * @param array           $attribute // New attributes for partner config
     *
     * @return mixed
     */
    public function updatePartnerConfigForSubmerchant(Merchant\Entity $merchant, array $appIds, array $attribute)
    {
        $existingPartnerConfigsForMerchant = $this->repo->partner_config->fetchOverriddenConfigsByMerchantId($appIds, $merchant->getId());

        $response = new Base\PublicCollection();

        $this->validatePricingPlans($attribute);

        foreach ($existingPartnerConfigsForMerchant as $config)
        {

            $config->edit($attribute, 'edit');

            $this->repo->saveOrFail($config);

            $response->push($config->toArrayPublic());
        }

        return $response->toArrayWithItems();
    }

    /**
     * The following function creates/updates partner configs associated with partner
     *
     * @param Merchant\Entity $merchant  // Submerchant entity
     * @param Merchant\Entity $partner   // Partner entity
     * @param array           $appIds    // Lists of appIds the submerchant is linked to
     * @param array           $attribute // New attributes for partner config E.g.: implicit_plan_id
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    public function createPartnerConfigForSubmerchant(Merchant\Entity $merchant, Merchant\Entity $partner, array $appIds, array $attribute)
    {
        $response = new Base\PublicCollection();

        $partnerConfigsForAppIds = $this->repo->partner_config->fetchDefaultConfigForAppIds($appIds);

        $applicationByAppId = $this->getApplicationsForSubmerchantLinkedApps($partner, $appIds);

        foreach ($partnerConfigsForAppIds as $config)
        {

            $application = $applicationByAppId[$config->getEntityId()];

            $newConfig = $this->getClonedPartnerConfig($config, $attribute);

            $newConfig = $this->create($application, $newConfig, $merchant);

            $response->push($newConfig->toArrayPublic());
        }

        return $response->toArrayWithItems();
    }

    /**
     * 1. Clones the config provided for AppId
     * 2. Prepares input data with attributes provided and cloned data required for creating a new Partner config
     *
     * @param Entity $config    // Partner Config to be cloned
     * @param        $attribute // New attributes for partner config
     *
     * @return array
     */
    public function getClonedPartnerConfig(Entity $config, $attribute)
    {
        $newConfig = $config->replicate();

        $newConfig = $newConfig->toArray();

        $newConfig = array_only($newConfig, $config->getFillable());

        $newConfig[Constants::APPLICATION_ID] = $config->getEntityId();

        $newConfig = array_merge($newConfig, $attribute);

        if (empty($newConfig->defaultPaymentsMethods) === true)
        {

            unset($newConfig[Entity::DEFAULT_PAYMENT_METHODS]);
        }

        return $newConfig;
    }

    /**
     * This function returns all the submerchant linked applications
     *
     * @param $partner // Partner entity
     * @param $appIds  // ApplicationIds the submerchant linked to
     *
     * @return array
     */
    private function getApplicationsForSubmerchantLinkedApps(Merchant\Entity $partner, array $appIds)
    {

        $applicationById = [];

        $appType = ($partner->isPurePlatformPartner() === true) ? null : Application\Type::PARTNER;

        $partnerApplications = (new Application\Repository)->findActiveApplicationsByMerchantIdAndType($partner->getId(), $appType);

        foreach ($partnerApplications as $application)
        {

            if (in_array($application->getId(), $appIds))
            {

                $applicationById[$application->getId()] = $application;
            }
        }

        return $applicationById;
    }

    /**
     * This function updates the application for all configs associated with provided existing application id.
     *
     * @param string $existingAppId     ApplicationId associated with Partner config
     * @param string $appId             ApplicationId to update the partner config
     *
     * @return void
     */
    public function updateApplicationsForPartnerConfigs(string $existingAppId, string $appId)
    {
        $configs = $this->repo->partner_config->fetchAllConfigForApps([$existingAppId]);

        foreach($configs as $config)
        {
            if ($config->getOriginId() === $existingAppId)
            {
                $config->setOriginId($appId);
            }

            if ($config->getEntityId() === $existingAppId)
            {
                $config->setEntityId($appId);
            }
        }

        $this->repo->saveOrFailCollection($configs);
    }

    public function fetchPartnersManagedApplicationConfig(Merchant\Entity $partner)
    {
        $appIds = (new Merchant\Core)->getPartnerApplicationIds($partner, [MerchantApplications\Entity::MANAGED]);

        if(empty($appIds) === true)
        {
            return null;
        }

        $partnerConfig = $this->repo->partner_config->getApplicationConfig($appIds[0]);

        return $partnerConfig;
    }

    /**
     * This function is used to audit partner config entity
     * @param   array   $params     associative array of format
     *  [
     *      'entity'        => Array,
     *      'entity_name'   => String,
     *      'actor_id'      => String,
     *      'actor_email'   => String
     *  ]
     *
     * @return  array    array is an associative array of the format ['status_code' => Int, 'response' => Object];
     */
    public function auditPartnerConfig(array $params)
    {
        $entity = $params['entity'];

        $this->trace->info(TraceCode::PARTNER_CONFIG_AUDIT_JOB_REQUEST,
           [
               'mode'    => $this->mode,
               'params'  => $params,
           ]
        );

        $request = $this->getAuditData($entity, $params);
        return $this->app->partnerships->createAuditLog($request, Mode::LIVE);
    }

    private function getAuditData($entity, $params)
    {
        $auditLog =  [
            'entity_id'             => $entity[Entity::ID],
            'entity_type'           => $params['entity_name'],
            'actor_id'              => $params['actor_id'],
            'actor_type'            => $params['actor_type'],
            'actor_email'           => $params['actor_email'],
            'meta_data'             => $params['meta_data'],
            'entity_data'           => json_decode(json_encode($entity)),
            'modified_at'           => $entity[Entity::UPDATED_AT]
        ];

        return [
            'audit_log'             => json_decode(json_encode($auditLog)),
        ];
    }

    /**
     * @param array $input
     * @return  void
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BaseException
     */
    public function uploadLogo(array &$input): void
    {
        if (isset($input[Constants::LOGO]) === false)
        {
            return;
        }

        try
        {
            // Store the logo in AWS S3 bucket
            $logoUrl = (new Merchant\Logo())->setUpMerchantLogo($input);

            $input[Entity::PARTNER_METADATA][Constants::LOGO_URL] = $logoUrl;

            unset($input[Constants::LOGO]);
        }
        catch(Exception\BadRequestException $exception)
        {
            $errorCode = $exception->getCode();

            $mappedErrorCode = Constants::PARTNER_CONFIG_LOGO_ERROR_MAP[$errorCode] ?? null;

            if (empty($mappedErrorCode) === true)
            {
                throw $exception;
            }

            throw new Exception\BadRequestException($mappedErrorCode);
        }
    }

    /**
     * Append the existing partner_metadata in the input to avoid overwriting
     * Common fields will be updated based on the input
     *
     * @param Entity $partnerConfig
     * @param array $input
     *
     * @return  void
     */
    protected function buildPartnerMetadata(Entity $partnerConfig, array & $input): void
    {
        if (empty($input[Entity::PARTNER_METADATA]) === true)
        {
            return;
        }

        $existingMetadata = $partnerConfig->getPartnerMetadata();

        if (empty($existingMetadata) === false)
        {
            $input[Entity::PARTNER_METADATA] = array_merge($existingMetadata, $input[Entity::PARTNER_METADATA]);
        }
    }
}
