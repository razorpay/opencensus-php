<?php

namespace RZP\Models\Merchant\MerchantApplications;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param Merchant\Entity $merchant
     * @param array|null $input
     *
     * @return Entity
     */
    public function create(
        Merchant\Entity $merchant,
        array $input = null)
    {
        $merchantApplication = (new Entity)->build($input);

        $merchantApplication->generateId();

        $merchantApplication->merchant()->associate($merchant);

        $this->repo->saveOrFail($merchantApplication);

        return $merchantApplication;
    }

    public function deleteMultipleApplications(array $applicationIds)
    {
        $merchantApplications  = $this->repo->merchant_application->fetchMerchantApplicationByAppIds($applicationIds);

        $this->trace->info(
            TraceCode::MERCHANT_APPLICATIONS_DELETE,
            [
                'application_ids' => $merchantApplications->pluck(Entity::APPLICATION_ID)->toArray(),
            ]
        );

        foreach ($merchantApplications as $merchantApplication)
        {
            $this->repo->deleteOrFail($merchantApplication);
        }
    }

    public function deleteByApplication(string $applicationId)
    {
        $this->delete($applicationId, Merchant\Constants::APPLICATION_ID);
    }

    protected function delete(string $entityId, string $entityType)
    {
        $merchantApplications  = $this->repo
                                      ->merchant_application
                                      ->fetchMerchantApplication($entityId, $entityType);

        $applicationIds = $merchantApplications->pluck(Entity::APPLICATION_ID)->toArray();

        $this->trace->info(
            TraceCode::MERCHANT_APPLICATIONS_DELETE,
            [
                'application_ids' => $applicationIds,
            ]
        );

        foreach ($merchantApplications as $merchantApplication)
        {
            $this->repo->deleteOrFail($merchantApplication);
        }
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return string
     */
    public function getDefaultAppTypeForPartner(Merchant\Entity $merchant): string
    {
        if ($merchant->isPurePlatformPartner() === true)
        {
            return Entity::OAUTH;
        }
        else if ($merchant->isResellerPartner() === true)
        {
            return Entity::REFERRED;
        }
        else
        {
            return Entity::MANAGED;
        }
    }

    /**
     * @param string $appId
     *
     * @return bool
     */
    public function isMerchantAppPresent(string $appId): bool
    {
        $response = $this->repo
                         ->merchant_application
                         ->fetchMerchantApplication($appId, Entity::APPLICATION_ID);

        if ($response->isEmpty() === true)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function getMerchantAppIds(string $merchantId, array $types = [], string $appId = null): array
    {
        return $this->repo
                    ->merchant_application
                    ->fetchMerchantApplications($merchantId, $types, null, false, $appId)
                    ->pluck(Entity::APPLICATION_ID)
                    ->toArray();
    }
}
