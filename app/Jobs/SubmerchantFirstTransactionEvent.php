<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Constants;
use RZP\Models\Partner\Metric as PartnerMetric;


class SubmerchantFirstTransactionEvent extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 1;

    public $timeout = 1800;

    protected $limit;

    protected $afterId;

    protected $mock;

    public function __construct(string $mode, $input)
    {
        parent::__construct($mode);

        $this->limit    = $input['limit'] ?? null;

        $this->afterId  = $input['afterId'] ?? null;

        $this->mock     = $input['mock'] ?? false;
    }

    /**
     * Fetches all the submerchants whose first ever payment is after the lastCronTime and trigger the segment events to all the affiliated partners.
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $startTime         = millitime();
            $numBatches        = 0;
            $subMerchantCount  = 0;
            $pageSize          = Constants::DAILY_TRANSACTED_SUBMERCHANTS_JOB_PAGE_SIZE;
            $batchSize         = Constants::DAILY_TRANSACTED_SUBMERCHANTS_BATCH_SIZE;
            $limit             = $this->limit ?? Constants::DAILY_TRANSACTED_SUBMERCHANTS_LIMIT;

            $lastCronTime = $this->getLastCronTime(Constants::SUBMERCHANT_FIRST_TRANSACTION_CRON_CACHE_KEY);

            while ($subMerchantCount < $limit)
            {
                // Filter out all sub merchants  that have transacted since last time cron ran
                $transactedMerchants = $this->repoManager->merchant_access_map->getTransactedSubmerchants(
                    $this->afterId, $pageSize, $lastCronTime);

                $this->trace->info(TraceCode::SUBMERCHANT_FIRST_TRANSACTION_DETAILS_CRON_TRACE, [
                    'last_cron_time'  => $lastCronTime,
                    'type'            => 'submerchant_first_transaction_cron',
                    'merchants_count' => count($transactedMerchants),
                    'time_taken'      => millitime() - $startTime,
                ]);

                if (empty($transactedMerchants) === true)
                {
                    break;
                }

                $this->afterId = last($transactedMerchants);

                $merchantIdsChunks = array_chunk($transactedMerchants, $batchSize);

                foreach ($merchantIdsChunks as $merchantBatch)
                {
                    if($subMerchantCount >= $limit)
                        break;

                    $leftSubmerchantCount = $limit - $subMerchantCount;
                    if($leftSubmerchantCount < $batchSize)
                        $merchantBatch = array_slice($merchantBatch, 0, $leftSubmerchantCount);

                    if($this->mock === false)
                    {
                        SendSubmerchantFirstTransactionSegementEvents::dispatch($this->mode, $merchantBatch);
                    }

                    $subMerchantCount += count($merchantBatch);
                    $numBatches++;
                }
            }

            //Update last Cron time instantly
            $this->updateLastCronTime(Constants::SUBMERCHANT_FIRST_TRANSACTION_CRON_CACHE_KEY);

            $timeTaken = millitime() - $startTime;

            $this->trace->histogram(PartnerMetric::SUBMERCHANT_FIRST_TRANSACTION_LATENCY_IN_MS, $timeTaken);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SUBMERCHANT_FIRST_TRANSACTION_JOB_ERROR,
                [
                    'mode'        => $this->mode,
                    'afterId'     => $this->afterId,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::SUBMERCHANT_FIRST_TRANSACTION_QUEUE_DELETE, [
                'mode'         => $this->mode,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    private function getLastCronTime(string $cacheKey)
    {
        $lastCronTime = $this->cache->get($cacheKey);

        if (empty($lastCronTime))
        {
            return Carbon::now()->subDays(Constants::DEFAULT_LAST_CRON_SUB_DAYS)->getTimestamp();
        }

        return $lastCronTime;
    }

    private function updateLastCronTime(string $cacheKey)
    {
        $this->cache->put($cacheKey, Carbon::now()->getTimestamp());
    }
}
