<?php

namespace RZP\Models\Application\ApplicationMerchantMaps;

use RZP\Models\Base;
use RZP\Models\Application\Entity as AppEntity;
use RZP\Models\Application\ApplicationTags\Entity as AppMappingEntity;

class Core extends Base\Core
{

    public function create(array $input): Entity
    {
        $appMerchantMappingEntity = $this->repo->application_merchant_mapping->getAppMerchantMap(
                                    $input[Entity::APP_ID], $input[Entity::MERCHANT_ID]);

        if (empty($appMerchantMappingEntity) === true)
        {
            $appMerchantMappingEntity = new Entity;

            $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

            $appMerchantMappingEntity->merchant()->associate($merchant);

            $appMerchantMappingEntity->setAppId($input[Entity::APP_ID]);
        }

        $appMerchantMappingEntity->setEnabled(true);

        $this->repo->saveOrFail($appMerchantMappingEntity);

        return $appMerchantMappingEntity;
    }

    public function update(Entity $appMerchantMappingEntity, array $input): Entity
    {
        $appMerchantMappingEntity->setEnabled($input[Entity::ENABLED]);

        $this->repo->saveOrFail($appMerchantMappingEntity);

        return $appMerchantMappingEntity;
    }

    public function get(string $id, array $input): array
    {
        /** @var Entity $app */
        $usedAppList = $this->repo->application_merchant_mapping->getEnabledAppsForMerchant($id);

        $usedAppIdList = [];

        $preferenceAppList = [];

        $preferenceAppIdList = [];

        foreach ($usedAppList as $usedApp)
        {
            array_push($usedAppIdList, $usedApp[Entity::APP_ID]);
        }

        $merchantTagEntity = $this->repo->application_merchant_tag->getMerchantTag($id);

        if (empty($merchantTagEntity) === false)
        {
            $selectAttr = [
                \RZP\Models\Application\ApplicationTags\Entity::APP_ID,
            ];

            $preferenceAppList = $this->repo->application_mapping->getAppMappingByTag($merchantTagEntity[AppMappingEntity::TAG], true, $selectAttr);
        }

        foreach ($preferenceAppList as $preferenceApp)
        {
            array_push($preferenceAppIdList, $preferenceApp[Entity::APP_ID]);
        }

        $allApps = $this->repo->application->getAllApps();

        $finalList = [];

        if (isset($input[AppEntity::HOME_APP]))
        {
            $finalList = $this->homePageScreenAppList($usedAppIdList, $preferenceAppIdList, $allApps);
        }
        else
        {
            $finalList = $this->appPageScreenAppList($preferenceAppIdList, $allApps);
        }

        return $finalList;
    }

    private function appPageScreenAppList(array $preferenceAppIdList, array $allApps)
    {
        $finalList = [];

        foreach ($allApps as $app)
        {
            if (in_array($app[AppEntity::ID], $preferenceAppIdList))
            {
                $app[Entity::SUGGESTED_APP] = true;

                array_push($finalList, $app);
            }
            else
            {
                $app[Entity::AVAILABLE_APPS] = true;

                array_push($finalList, $app);
            }
        }

        return $finalList;
    }

    private function homePageScreenAppList(array $usedAppIdList, array $preferenceAppIdList, array $allApps)
    {
        $finalList = [];

        if (count($preferenceAppIdList) > 0)
        {
            foreach ($allApps as $app)
            {
                if (in_array($app[AppEntity::ID], $usedAppIdList) && $app[AppEntity::HOME_APP] === 1)
                {
                    $app[Entity::USED_APP] = true;

                    array_push($finalList, $app);
                }
                else if (in_array($app[AppEntity::ID], $preferenceAppIdList) && $app[AppEntity::HOME_APP] === 1)
                {
                    $app[Entity::SUGGESTED_APP] = true;

                    array_push($finalList, $app);
                }
            }
        }
        else
        {
            foreach ($allApps as $app)
            {
                if (in_array($app[AppEntity::ID], $usedAppIdList))
                {
                    $app[Entity::USED_APP] = true;

                    array_push($finalList, $app);
                }
                else
                {
                    $app[Entity::AVAILABLE_APPS] = true;

                    array_push($finalList, $app);
                }
            }
        }

        return $finalList;
    }
}
