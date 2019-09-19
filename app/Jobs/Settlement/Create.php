<?php

namespace RZP\Jobs\Settlement;

use Cache;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement\Metric;
use RZP\Exception\BadRequestException;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Initiator;
use RZP\Models\Settlement\Processor as SettlementProcessor;

class Create extends Job
{
    //
    // redis keys used to store intermediate count of settlement process
    //
    const TOTAL_MERCHANT_COUNT  = '{settlement}_total_merchant_count_%s';

    const CHANNEL_WISE_COUNT    = '{settlement}_channel_wise_count_%s';

    /**
     * @var string
     */
    protected $queueConfigKey = 'settlement_create';

    /**
     * @var string
     */
    protected $settlementBucket;

    /**
     * @var array
     */
    protected $merchantId;

    /**
     * Here, we fetch merchantId and their corresponding unsettled transactionIds.
     *
     * @param string $mode
     * @param string $merchantId
     * @param null   $settlementBucket sending this only to analyze whether this merchant is taken from bucket or not
     */
    public function __construct(string $mode, string $merchantId, $settlementBucket = null)
    {
        parent::__construct($mode);

        $this->merchantId       = $merchantId;

        $this->settlementBucket = $settlementBucket;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        $merchant = null;

        try
        {
            $key = sprintf(self::TOTAL_MERCHANT_COUNT, $this->mode);

            // reduce the total count once the processing is done
            Cache::decrement($key);

            $this->trace->info(
                TraceCode::SETTLEMENT_JOB_INIT_FOR_MERCHANT,
                [
                    'merchant_id'       => $this->merchantId,
                    'settlement_bucket' => $this->settlementBucket,
                ]);

            $merchant = $this->repoManager->merchant->findOrFail($this->merchantId);

            $startTime = microtime(true);

            $setlResponse = (new SettlementProcessor)->fetchAndProcessTransactionsForSettlement($merchant);

            $response = [
                'merchant_id'   => $this->merchantId,
                'mode'          => $this->mode,
                'channel'       => $merchant->getChannel(),
                'time_taken'    => get_diff_in_millisecond($startTime),
            ] + $setlResponse;

            $this->trace->info(
                TraceCode::SETTLEMENT_ATTEMPT_ENTITIES_CREATED_FOR_MERCHANT,
                $response);
        }
        catch (BadRequestException $e)
        {
            if ($e->getCode() === ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS)
            {
                $key = sprintf(self::TOTAL_MERCHANT_COUNT, $this->mode);

                //
                // its been seen that one job is received by multiple workers with in 10-15 sec of delay
                // In any case if this happens the settlement count will get messed up
                // in case of mutex error we are incrementing the counter here
                // this is to keep the count stable in further process
                //
                Cache::increment($key);
            }
        }
        catch (\Throwable $e)
        {
            $data = [
                'merchant_id'       => $this->merchantId ,
                'mode'              => $this->mode,
            ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENTS_PROCESS_FAILED_FOR_MERCHANT,
                $data);

            $operation = 'Settlement creation failed for MID: ' . $this->merchantId;

            (new SlackNotification)->send($operation, $data, $e);
        }
        finally
        {
            $this->delete();

            $this->trace->count(
                Metric::MERCHANT_SETTLEMENT_PROCESSED,
                [
                    'channel' => $merchant->getChannel(),
                ]);

            $this->dispatchForSettlementInitiateIfRequired($merchant->getChannel());
        }
    }

    /**
     * takes care of triggering settlement initiate
     * if there are sufficient amount of settlement available based on channel
     * it'll also trigger the same if settlement create process is complete
     *
     * @param string $channel
     */
    protected function dispatchForSettlementInitiateIfRequired(string $channel)
    {
        $redis = app('redis')->connection();

        $channelCountKey = sprintf(self::CHANNEL_WISE_COUNT, $this->mode);

        $count = (int) $redis->hincrby($channelCountKey, $channel, 1);

        $batchSize = (new Initiator)->getLimitForChannel($channel);

        // if there enough settlement to transfer then initiate the transfer
        if ($count === $batchSize)
        {
            $this->dispatchForSettlementInitiate($redis, $channel, $batchSize);

            return;
        }

        $key = sprintf(self::TOTAL_MERCHANT_COUNT, $this->mode);

        // if there total merchant count is zero that means settlement creation process completed
        $isCompleted = (((int) Cache::get($key)) === 0);

        // if process is not complete then do not initiate transfer
        if ($isCompleted === false)
        {
            return;
        }

        $channelCount = $redis->hgetall($channelCountKey);

        // If there any channel with pending settlement initiate then dispatch it for the same
        foreach ($channelCount as $ch => $count)
        {
            if (((int) $count) !== 0)
            {
                $this->dispatchForSettlementInitiate($redis, $ch, $count);
            }
        }
    }

    /**
     * dispatch channel to initiate settlement
     *
     * @param        $redis
     * @param string $channel
     * @param        $count
     */
    protected function dispatchForSettlementInitiate($redis, string $channel, $count)
    {
        Initiate::dispatch($this->mode, $channel);

        $this->trace->info(
            TraceCode::DISPATCH_FOR_SETTLEMENT_INITIATE,
            [
                'channel' => $channel,
                'count'   => $count,
            ]);

        $this->trace->count(
            Metric::DISPATCH_FOR_SETTLEMENT_INITIATE,
            [
                'channel' => $channel,
            ]);

        $channelCountKey = sprintf(self::CHANNEL_WISE_COUNT, $this->mode);

        // decrement the size by count as those are dispatched to initiate
        $redis->hincrby($channelCountKey, $channel, -1 * $count);
    }
}
