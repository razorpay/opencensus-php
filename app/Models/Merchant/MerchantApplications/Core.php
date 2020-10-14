<?php

namespace RZP\Models\Merchant\MerchantApplications;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
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

    public function deleteMerchantApplication(string $entityId, string $entityType)
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
}
