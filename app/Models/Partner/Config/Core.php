<?php

namespace RZP\Models\Partner\Config;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
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
        return $this->repo->partner_config->fetchAllConfigForApp($application->getId());
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

        $config->edit($input, 'edit');

        $this->repo->saveOrFail($config);

        return $config;
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
}
