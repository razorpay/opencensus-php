<?php

namespace RZP\Models\Partner\Config\SubMerchantConfig;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Base\EsRepository;
use RZP\Models\Merchant;
use RZP\Models\Merchant\BusinessDetail\Service;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner\Metric;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Partner\Config\Constants;
use RZP\Models\Workflow\Action\Differ;

class Core extends Base\Core
{
    public function fetchPartnerSubMerchantConfig(Merchant\Entity $partner,string $attributeName)
    {
        $partnerConfig = (new PartnerConfig\Core)->fetchPartnersManagedApplicationConfig($partner);

        if(empty($partnerConfig) === true)
        {
            return null;
        }
        $subMerchantConfig = $partnerConfig->getSubMerchantConfig();

        if(empty($subMerchantConfig) == true or empty($subMerchantConfig[$attributeName]) == true)
        {
            return null;
        }

        return $subMerchantConfig[$attributeName];
    }

    public function createPartnersSubMerchantConfig(Merchant\Entity $partner, array $input)
    {
        $partnerConfig = (new PartnerConfig\Core)->fetchPartnersManagedApplicationConfig($partner);

        $subMerchantConfig = $partnerConfig->getSubMerchantConfig();

        $newSubMerchantConfig = $this->buildSubMerchantConfig($subMerchantConfig, $input);

        $newPartnerConfig = clone $partnerConfig;

        $newPartnerConfig->setSubMerchantConfig($newSubMerchantConfig);

        $attributeName = $input[Constants::ATTRIBUTE_NAME];

        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false and in_array($attributeName, Constants::requiresWorkflow, true) === true)
        {
            $this->trace->info(
                TraceCode::CREATE_PARTNERS_SUBMERCHANT_CONFIG_WORKFLOW_TRIGGERED,
                [
                    'old_config' => $subMerchantConfig,
                    'new_config' => $newSubMerchantConfig,
                ]);

            $partnerConfig->setSubMerchantConfig(json_encode($subMerchantConfig));

            $newPartnerConfig->setSubMerchantConfig(json_encode($newSubMerchantConfig));

            $this->app['workflow']
                ->setPermission(Constants::attributePermissionMap[$attributeName])
                ->setEntity($partnerConfig->getEntity())
                ->handle($partnerConfig, $newPartnerConfig);
        }

        $this->repo->partner_config->saveOrFail($newPartnerConfig);

        $this->trace->info(
            TraceCode::CREATE_PARTNERS_SUBMERCHANT_CONFIG_SUCCESS,
            [
                'old_config' => $subMerchantConfig,
                'new_config' => $newSubMerchantConfig,
            ]);

        $dimensionsForPartnersSubmerchantConfig =  [
            Constants::ATTRIBUTE_NAME   => $attributeName
        ];

        $this->trace->count(Metric::PARTNER_SUB_MERCHANT_CONFIG_CREATE_TOTAL, $dimensionsForPartnersSubmerchantConfig);

        return $newPartnerConfig;
    }

    public function updatePartnersSubMerchantConfig(Merchant\Entity $partner, array $input)
    {
        $partnerConfig = (new PartnerConfig\Core)->fetchPartnersManagedApplicationConfig($partner);

        $attributeName = $input[Constants::ATTRIBUTE_NAME];

        $subMerchantConfig = $partnerConfig->getSubMerchantConfig();

        $newSubMerchantConfig = $this->removeSubMerchantConfigJson($subMerchantConfig,$input[Constants::PARAMETERS],$attributeName);

        $diff = (new Differ\Core)->createDiff($subMerchantConfig,$newSubMerchantConfig);

        if(empty($diff) === true){
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                Entity::SUB_MERCHANT_CONFIG,
                $input);
        }

        $newPartnerConfig = clone $partnerConfig;

        $newPartnerConfig->setSubMerchantConfig($newSubMerchantConfig);

        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false and in_array($attributeName, Constants::requiresWorkflow, true) === true)
        {
            $this->trace->info(
                TraceCode::UPDATE_PARTNERS_SUBMERCHANT_CONFIG_WORKFLOW_TRIGGERED,
                [
                    'old_config' => $subMerchantConfig,
                    'new_config' => $newSubMerchantConfig,
                ]);

            $partnerConfig->setSubMerchantConfig(json_encode($subMerchantConfig));

            $newPartnerConfig->setSubMerchantConfig(json_encode($newSubMerchantConfig));

            $this->app['workflow']
                ->setPermission(Constants::attributePermissionMap[$attributeName])
                ->setEntity($partnerConfig->getEntity())
                ->handle($partnerConfig, $newPartnerConfig);
        }

        $this->repo->partner_config->saveOrFail($newPartnerConfig);

        $this->trace->info(
            TraceCode::UPDATE_PARTNERS_SUBMERCHANT_CONFIG_SUCCESS,
            [
                'old_config' => $subMerchantConfig,
                'new_config' => $newSubMerchantConfig,
            ]);

        $dimensionsForPartnersSubmerchantConfig =  [
            Constants::ATTRIBUTE_NAME   => $attributeName
        ];

        $this->trace->count(Metric::PARTNER_SUB_MERCHANT_CONFIG_UPDATE_TOTAL, $dimensionsForPartnersSubmerchantConfig);

        return $newPartnerConfig;
    }

    protected function removeSubMerchantConfigJson(array $subMerchantConfig, array $parameters, string $attributeName)
    {
        if(empty($subMerchantConfig[$attributeName]) === true)
        {
            return $subMerchantConfig;
        }

        $parametersIndex = $this->findParametersInSubMerchantConfig($parameters,$subMerchantConfig[$attributeName]);

        if($parametersIndex!==-1)
        {
            array_splice($subMerchantConfig[$attributeName],$parametersIndex,1);
        }
        return $subMerchantConfig;
    }

    protected function buildSubMerchantConfig(?array $subMerchantConfig, array $input)
    {
        $attributeName = $input[Constants::ATTRIBUTE_NAME];

        $config[Constants::VALUE] = $input[Constants::VALUE];

        $parameters = $input[Constants::PARAMETERS];

        foreach($parameters as $key => $value)
        {
            $config[$key] = $value;
        }

        if(empty($subMerchantConfig) === true or empty($subMerchantConfig[$attributeName]) === true)
        {
            $subMerchantConfig[$attributeName] = [$config];
        }
        else
        {
            $parametersIndex = $this->findParametersInSubMerchantConfig($parameters,$subMerchantConfig[$attributeName]);

            if($parametersIndex !== -1)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                    PartnerConfig\Entity::SUB_MERCHANT_CONFIG,
                    $parameters);
            }

            $subMerchantConfig[$attributeName][] = $config;
        }

        return $subMerchantConfig;
    }

    protected function findParametersInSubMerchantConfig(array $parameters, array $subMerchantConfig)
    {
        foreach($subMerchantConfig as $subMerchantConfigKey => $subMerchantConfigValue)
        {
            $isParametersPresent = true;

            foreach($parameters as $key => $value)
            {
                if($subMerchantConfigValue[$key] !== $value)
                {
                    $isParametersPresent = false;
                }
            }

            if($isParametersPresent === true)
            {
                return $subMerchantConfigKey;
            }
        }
        return -1;
    }

    public function updateOnboardingSource(array $input)
    {
        $onboardingSource = $input[Constants::ONBOARDING_SOURCE];

        $esRepo = new EsRepository(DetailConstants::DEDUPE_ES_INDEX);

        $merchantIds = $input[Constants::MERCHANT_IDS];

        foreach ( $merchantIds as $mid)
        {
            $businessDetailService = new Service();

            $businessDetailService->saveBusinessDetailsForMerchant($mid, [
                DetailConstants::ONBOARDING_SOURCE => $onboardingSource
            ]);

            $esRepo->storeOrUpdateDocument($mid, DetailConstants::DEDUPE_ES_INDEX, $onboardingSource, null);
        }
    }
}
