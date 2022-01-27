<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Escalations\Core as EscalationCore;

class SendNotificationAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["notify_merchants"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data["merchantIds"];

        if (count($merchantIds) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = (new EscalationCore())->sendNotificationUtility(
            $merchantIds,
            $this->args['event_name']
        );

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIds)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }
}
