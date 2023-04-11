<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;

class TriggerWANotificationToIntlMerchantsDataCollector extends TimeBoundDbDataCollector
{
    const ANALYTICS_QUERY = " select * from hive.aggregate_ba.merchant_segment_fact_marketing " .
                 " where last_landing_page like '%%international%%' and " .
                 " international_enabled = 'No' and " .
                 " signup_date between cast('%s' as date) and cast('%s' as date) ";

    const ANALYTICS_QUERY2 = " select ms.merchant_id, ms.contact_name, ms.contact_mobile from hive.aggregate_ba.merchant_segment_fact_marketing ms " .
                 " join hive.realtime_hudi_api.merchant_details md on ms.merchant_id = md.merchant_id " .
                 " where ms.international_enabled = 'No' and " .
                 " ms.last_landing_page like '%%international%%' and " .
                 " md.activation_flow in ('whitelist', 'greylist') and " .
                 " md.international_activation_flow is null and " .
                 " md.created_at between %s and %s ";

    const STORK_QUERY = " select json_extract_scalar(properties, '$.message_id') from hive.events.events_message_channel_whatsapp_v2 " .
                 " where json_extract_scalar(properties, '$.owner_type') = 'merchant' and " .
                 " json_extract_scalar(properties, '$.owner_id') =  '%s' and " .
                 " json_extract_scalar(properties, '$.message_channel_context.template') = '%s' and " .
                 " json_extract_scalar(properties, '$.attempt_response_code') = '200' and " .
                 " event_name = 'message_channels.whatsapp.attempted' ";

    // cron start time
    protected function getStartInterval(): int
    {
        return Carbon::yesterday(Timezone::IST)->startOfDay()->getTimestamp();
        // return  Carbon::now(Timezone::IST)->subDays(1)->isoFormat('YYYY-MM-DD');
    }

    // cron end time
    protected function getEndInterval(): int
    {
        return Carbon::yesterday(Timezone::IST)->endOfDay()->getTimestamp();
        // return  Carbon::now(Timezone::IST)->subDays(1)->isoFormat('YYYY-MM-DD');
    }

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $query = $this->getMerchantSignupSourceQuery($startTime, $endTime, $this->args);

        $dataLakeResponse = $this->fetchFromDatalake($query, $this->args);

        if (count($dataLakeResponse) < 1 )
        {
            return CollectorDto::create([]);
        }

        $merchantData = [];

        foreach ($dataLakeResponse as $record)
        {
            $query = $this->getStorkWAEventsQuery($record['merchant_id']);

            $dataLakeResponse = $this->fetchFromDatalake($query, $this->args);

            // check if no msgs were prev sent to the same mid
            if (count($dataLakeResponse) < 1 )
            {
                $data = [
                    "id"            => $record['merchant_id'],
                    "name"          => $record['contact_name'],
                    "mobile"        => $record['contact_mobile'],
                ];


                array_push($merchantData, $data);
            }
        }

        return CollectorDto::create($merchantData);
    }

    protected function getMerchantSignupSourceQuery($startTime, $endTime, $input)
    {
        $query = sprintf(self::ANALYTICS_QUERY2, $startTime, $endTime);

        if (isset($input['merchant_id']) === true)
        {
            $merchantQuery = sprintf("merchant_id = '%s'", $input['merchant_id']);
            $query = $query . " and " . $merchantQuery;
        }

        return $query;
    }

    protected function getStorkWAEventsQuery($merchantId)
    {
        $query = sprintf(self::STORK_QUERY, $merchantId, Constants::WHATSAPP_TEMPLATE_NAME[$this->args["event_name"]]);

        return $query;
    }

    protected function fetchFromDatalake($dataLakeQuery, $input)
    {
        $queryStartTime = Carbon::now(Timezone::IST)->getTimestamp();

        $dataLakeResponse = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $queryEndTime = Carbon::now(Timezone::IST)->getTimestamp();

        $traceDataLakeResponse = [
            'input'                 => $input,
            'data_lake_query'       => $dataLakeQuery,
            'query_start_time'      => $queryStartTime,
            'query_end_time'        => $queryEndTime,
            'data_lake_count'       => count($dataLakeResponse),
        ];

        $this->app['trace']->info(TraceCode::CB_SIGNUP_JOURNEY_DATALAKE_RESPONSE, $traceDataLakeResponse);

        return $dataLakeResponse;
    }
}
