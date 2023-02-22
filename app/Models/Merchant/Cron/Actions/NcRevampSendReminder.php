<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Cron\Metrics;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\ClarificationDetail\Core as ClarificationCore;

class NcRevampSendReminder extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_notification_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data["merchantIds"];

        $this->app['trace']->count(Metrics::CRON_STARTED_TOTAL, [
            'nc_revamp_reminder_merchants' => count($merchantIds),
        ]);

        if (count($merchantIds) === 0)
        {
            $this->app['trace']->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_SKIPPED, [
                'type'            => 'sendNotification',
                'reason'          => 'no merchants found',
            ]);

            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                (new ClarificationCore())->sendReminderNotification($merchantId);

                $successCount++;
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->info(TraceCode::CRON_ATTEMPT_FAILURE, [
                    'merchants_count' => count($merchantId),
                    'type'            => 'sendEmail',
                ]);
            }
        }

        $this->app['trace']->count(Metrics::CRON_COMPLETE_TOTAL, [
            'nc_revamp_reminder_cron_successCount' => $successCount,
        ]);

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
