<?php

namespace RZP\Jobs\Settlement;

use Cache;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Merchant\Balance;
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
    protected $bucketTimestamp;

    /**
     * @var string
     */
    protected $merchantId;

    protected $totalMerchantCountKey;

    protected $channelWiseCountKey;

    protected $balanceType;

    protected $params;

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 900;

    /**
     * Here, we fetch merchantId and their corresponding unsettled transactionIds.
     *
     * @param string $mode
     * @param string $merchantId
     * @param null   $bucketTimestamp sending this only to analyze whether this merchant is taken from bucket or not
     * @param string $balanceType
     * @param array  $params
     */
    public function __construct(
        string $mode, string $merchantId, $bucketTimestamp, string $balanceType, array $params = [])
    {
        parent::__construct($mode);

        $this->merchantId       = $merchantId;

        $this->bucketTimestamp  = $bucketTimestamp;

        $this->balanceType      = $balanceType;

        $this->params           = $params;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        $merchant = $this->repoManager->merchant->findOrFail($this->merchantId);

        $channel = $merchant->getChannel();

        // commissions will be settled only from yes_bank channel
        if ($this->balanceType === Balance\Type::COMMISSION)
        {
            $channel = Settlement\Channel::YESBANK;
        }

        try
        {
            $this->totalMerchantCountKey = sprintf(self::TOTAL_MERCHANT_COUNT, $this->mode);

            $this->channelWiseCountKey   = sprintf(self::CHANNEL_WISE_COUNT, $this->mode);

            // reduce the total count once the processing is done
            Cache::decrement($this->totalMerchantCountKey);

            $this->trace->info(
                TraceCode::SETTLEMENT_JOB_INIT_FOR_MERCHANT,
                [
                    'merchant_id'       => $this->merchantId,
                    'bucket_timestamp'  => $this->bucketTimestamp,
                ]
            );

            $merchant = $this->repoManager->merchant->findOrFail($this->merchantId);

            $startTime = microtime(true);

            $setlResponse = (new SettlementProcessor)->fetchAndProcessTransactionsForSettlement(
                $merchant, $channel, $this->balanceType, $this->params);

            $response = [
                'merchant_id'   => $this->merchantId,
                'balance_type'  => $this->balanceType,
                'mode'          => $this->mode,
                'channel'       => $channel,
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
                //
                // its been seen that one job is received by multiple workers with in 10-15 sec of delay
                // In any case if this happens the settlement count will get messed up
                // in case of mutex error we are incrementing the counter here
                // this is to keep the count stable in further process
                //
                Cache::increment($this->totalMerchantCountKey);
            }

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
        catch (\Throwable $e)
        {
            $data = [
                'merchant_id'       => $this->merchantId ,
                'mode'              => $this->mode,
                'balance_type'      => $this->balanceType,
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
                    'channel' => $channel,
                ]);

            $this->dispatchForSettlementInitiateIfRequired($channel);
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

        $count = (int) $redis->hincrby($this->channelWiseCountKey, $channel, 1);

        $batchSize = (new Initiator)->getLimitForChannel($channel);

        // if there enough settlement to transfer then initiate the transfer
        if ($count === $batchSize)
        {
            $this->dispatchForSettlementInitiate($redis, $channel, $count);

            return;
        }

        // if there total merchant count is zero that means settlement creation process completed
        $isCompleted = (((int) Cache::get($this->totalMerchantCountKey)) === 0);

        // if process is not complete then do not initiate transfer
        if ($isCompleted === false)
        {
            return;
        }

        $channelCount = $redis->hgetall($this->channelWiseCountKey);

        // If there any channel with pending settlement initiate then dispatch it for the same
        foreach ($channelCount as $ch => $count)
        {
            $count = (int) $count;

            if ($count !== 0)
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
    protected function dispatchForSettlementInitiate($redis, string $channel, int $count)
    {
        // decrement the size by count as those are dispatched to initiate
        $redis->hincrby($this->channelWiseCountKey, $channel, -1 * $count);

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
    }
}
