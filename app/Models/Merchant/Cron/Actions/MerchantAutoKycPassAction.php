<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\CmmaEscalation;

class MerchantAutoKycPassAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_auto_kyc_pass_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $limitType = $data[Constants::CASE_TYPE] ?? null;

        $level = $data[Constants::LEVEL] ?? null;

        if ($merchantIds === null or count($merchantIds) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $merchantIdChunks = array_chunk($merchantIds, 20);

        foreach ($merchantIdChunks as $merchantIdList) {

            $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);

            // trigger CMMA escalations for merchants who are in activated mcc pending state but haven't breached the transactions
            (new CmmaEscalation)->triggerCMMAEscalation($merchants, $limitType, $level);

        }

        return new ActionDto(Constants::SUCCESS);
    }
}
