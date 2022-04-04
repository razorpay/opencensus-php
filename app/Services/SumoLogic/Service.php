<?php


namespace RZP\Services\SumoLogic;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;

class Service
{
    protected $sumoClient;

    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->sumoClient = new Client();
    }

    public function searchCount(
        string $query, int $fromTimestamp, int $toTimestamp, int $jobTimeout = Constants::JOB_DEFAULT_TIMEOUT): int
    {
        $result = $this->search($query, $fromTimestamp, $toTimestamp, $jobTimeout);

        return $result['messageCount'] ?? 0;
    }

    public function search(
        string $query, int $fromTimestamp, int $toTimestamp, int $jobTimeout = Constants::JOB_DEFAULT_TIMEOUT)
    {
        $payload = $this->preparePayload($query, $fromTimestamp, $toTimestamp);

        $jobId = $this->sumoClient->createSearchjob($payload);

        if(empty($jobId) === false)
        {
            return $this->processJob($jobId, $jobTimeout);
        }

        return [];
    }

    public function logSearch(array $input)
    {
        $query = $input['merchant_id'] ?? null;

        $fromTimestamp = $input['fromTimestamp'] ?? Carbon::today(Timezone::IST)->startOfDay()->getTimestamp();

        $toTimestamp = $input['toTimestamp'] ?? Carbon::now(Timezone::IST)->getTimestamp();

        $jobId = $input['job_id'] ?? null;

        $offset = $input['offset'] ?? 0;

        $limit = $input['limit'] ?? 10000;

        if (empty($jobId) === true)
        {
            $payload = $this->preparePayload($query, $fromTimestamp, $toTimestamp);

            $jobId = $this->sumoClient->createSearchjob($payload);
        }

        if (empty($jobId) === false)
        {
            return $this->fetchMessagesResult($jobId, $offset, $limit);
        }
        return [];
    }

    protected function fetchMessagesResult(string $jobId,int $offset,int $limit)
    {
        $result = $this->sumoClient->fetchJobResult($jobId);

        $pending = ["state" => "pending", "job_id" => $jobId];

        if (empty($result) === false)
        {
            if ($result['state'] === Constants::DONE_GATHERING_RESULTS)
            {
                return $this->sumoClient->fetchJobMessages($jobId, $offset, $limit);
            }

            return $pending;
        }
        else
        {
            return $pending;
        }
    }

    protected function processJob(string $jobId, int $jobTimeout)
    {
        $totalWaitTime = 0;

        while ($totalWaitTime < $jobTimeout)
        {
            $result = $this->sumoClient->fetchJobResult($jobId);

            if(empty($result) === false)
            {
                if($result['state'] === Constants::DONE_GATHERING_RESULTS)
                {
                    return $result;
                }
            }
            else
            {
                return [];
            }
            sleep(Constants::RETRY_WAIT_TIME);
            $totalWaitTime += Constants::RETRY_WAIT_TIME;
        }

        $this->trace->info(TraceCode::SUMO_LOGIC_JOB_TIMEDOUT, [
            'job_id'            => $jobId,
            'total_wait_time'   => $totalWaitTime,
            'job_timeout'       => $jobTimeout
        ]);

        return [];
    }

    private function preparePayload(string $query, int $fromTimestamp, int $toTimestamp): array
    {

        return [
            "query"         => $query,
            "from"          => Carbon::createFromTimestamp($fromTimestamp, Timezone::IST)->toDateTimeLocalString(),
            "to"            => Carbon::createFromTimestamp($toTimestamp, Timezone::IST)->toDateTimeLocalString(),
            "timeZone"      => "IST",
            "byReceiptTime" => true,
        ];
    }
}
