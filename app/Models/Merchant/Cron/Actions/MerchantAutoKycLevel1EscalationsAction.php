<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant\AutoKyc\Escalations\Handler;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;

class MerchantAutoKycLevel1EscalationsAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_escalation_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data["merchantIds"] ?? null;
        $merchantsGmvList = $data["merchantsGmvList"] ?? null;
        $limitType = $data["limitType"] ?? null;
        $level = $data["level"] ?? null;

        if ($merchantIds === null or count($merchantIds) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIds);

        (new Handler)->handleEscalations($merchants, $merchantsGmvList, $limitType, $level);

        return new ActionDto(Constants::SUCCESS);
    }
}
