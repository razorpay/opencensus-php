<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class AppsflyerInstallCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $response = $this->app['appsflyer']->getOrganicAndNonOrganicInstallEvents(
            date('Y-m-d', $startTime),
            date('Y-m-d', $endTime)
        );

        $result = [];

        foreach($response as $resp)
        {
            $csvData = $resp->getBody()->getContents();

            $data = str_getcsv($csvData, "\n"); //parse the rows

            foreach($data as &$row_)
            {
                $row_ = str_getcsv($row_);
            }

            $dataArray = [];

            foreach ($data as $row)
            {
                $dataArray[] = array_combine($data[0], $row);
            }

            array_shift($dataArray);

            $currResult = array_combine(array_column($dataArray,'AppsFlyer ID'), array_column($dataArray,'Event Time'));

            $result = array_merge($result, $currResult);
        }

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'data'          => $result
        ]);

        return CollectorDto::create($result);
    }

    protected function getStartInterval(): int
    {
        return Carbon::yesterday(Timezone::IST)->startOfDay()->getTimestamp();
    }

    protected function getEndInterval(): int
    {
        return Carbon::yesterday(Timezone::IST)->endOfDay()->getTimestamp();
    }
}
