<?php


namespace RZP\Models\Merchant\Cron\Actions;


use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Cron\Metrics;

class MtuReconAction extends BaseAction
{

    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $dataLakeCollector = $data["mtu_transacted_merchants"]; // since data collector is an array
        $sumoLogsCollector = $data["mtu_sumo_logs_count"];

        $merchantIdList = $dataLakeCollector->getData();
        $sumoLogsCount = $sumoLogsCollector->getData();

        $this->app['trace']->count(Metrics::MTU_TRANSACTED_EVENT_RECON_TOTAL, [
            'mtu_transacted_data_lake_count'        => count($merchantIdList),
            'mtu_transacted_segment_event_count'    => $sumoLogsCount
        ]);

        return new ActionDto(Constants::SUCCESS);
    }
}
