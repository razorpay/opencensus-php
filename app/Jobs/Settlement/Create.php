<?php

namespace RZP\Jobs\Settlement;

use Cache;
use Illuminate\Support\Facades\App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\Metric;
use RZP\Jobs\Transfers\TransferRecon;
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

    protected $pendingMerchantsForSettlement;

    protected $settlementPerChannelCount;

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
            $this->pendingMerchantsForSettlement = Cache::decrement($this->totalMerchantCountKey);

            $this->trace->info(
                TraceCode::SETTLEMENT_JOB_INIT_FOR_MERCHANT,
                [
                    'merchant_id'       => $this->merchantId,
                    'bucket_timestamp'  => $this->bucketTimestamp,
                ]
            );

            $merchant = $this->repoManager->merchant->findOrFail($this->merchantId);

            $startTime = microtime(true);

            $processor = (new SettlementProcessor);

            $setlResponse = $processor->fetchAndProcessTransactionsForSettlement(
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

            $this->incrementChannelCount($channel, $setlResponse['settlement_count']);

            if (empty($setlResponse['la_txn_ids']) === false)
            {
                $this->trace->info(
                    TraceCode::TRANSFER_SETTLEMENT_PROCESS_SQS_PUSH_INIT,
                    [
                        'txn_ids' => $setlResponse['la_txn_ids']
                    ]
                );

                $startTime = microtime(true);

                $laTxnIds = array_chunk($setlResponse['la_txn_ids'], 1000);

                foreach ($laTxnIds as $laTxnIdsChunk)
                {
                    $input['transaction_ids'] = $laTxnIdsChunk;

                    TransferRecon::dispatch($input, $this->mode);
                }

                $endTime = microtime(true);

                $this->trace->info(
                    TraceCode::TRANSFER_SETTLEMENT_SQS_PUSH_COMPLETE,
                    [
                        'count'         => count($setlResponse['la_txn_ids']),
                        'time_taken'    => $endTime - $startTime,
                    ]
                );
            }
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
                $this->pendingMerchantsForSettlement = Cache::increment($this->totalMerchantCountKey);
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

            $shouldInitiate = isset($this->params['daily_settlement']) ? false : true ;

            $this->dispatchForSettlementInitiateIfRequired($channel, $shouldInitiate);
        }
    }

    protected function incrementChannelCount(string $channel, int $count)
    {
        //
        // update the count for channel here
        // this would help to maintain the exact settlement create count
        //
        $redis = app('redis')->Connection('mutex_redis');

        $key = sprintf(Create::CHANNEL_WISE_COUNT, $this->mode);

        $this->settlementPerChannelCount = (int) $redis->hincrby($key, $channel, $count);

        $this->trace->count(
            Metric::SETTLEMENT_CREATED_COUNT,
            [
                'channel' => $channel,
            ]);
    }

    /**
     * takes care of triggering settlement initiate
     * if there are sufficient amount of settlement available based on channel
     * it'll also trigger the same if settlement create process is complete
     *
     * @param string $channel
     * @param bool $shouldInitiate
     */
    protected function dispatchForSettlementInitiateIfRequired(string $channel, $shouldInitiate = true)
    {
        $redis = app('redis')->Connection('mutex_redis');

        $channelCount = $redis->hGetAll($this->channelWiseCountKey);

        $count = $this->settlementPerChannelCount;

        $batchSize = (new Initiator)->getLimitForChannel($channel);

        $merchantCountKey = (int) $this->pendingMerchantsForSettlement;

        // if there total merchant count is zero that means settlement creation process completed
        $isCompleted = ($merchantCountKey === 0);

        $this->trace->info(
            TraceCode::SETTLEMENT_CREATE_REDIS_VALUES,
            [
                'channel'           => $channel,
                'merchant_id'       => $this->merchantId,
                'channel_count'     => $channelCount,
                'merchant_count'    => $merchantCountKey,
                'is_completed'      => $isCompleted,
            ]);

        // if there enough settlement to transfer then initiate the transfer
        if ($count >= $batchSize)
        {
            $this->dispatchForSettlementInitiate($redis, $channel, $count, $shouldInitiate);

            return;
        }

        // if process is not complete then do not initiate transfer
        if ($isCompleted === false)
        {
            return;
        }

        // If there any channel with pending settlement initiate then dispatch it for the same
        foreach ($channelCount as $ch => $count)
        {
            $count = (int) $count;

            if ($count !== 0)
            {
                $this->dispatchForSettlementInitiate($redis, $ch, $count, $shouldInitiate);
            }
        }
    }

    /**
     * dispatch channel to initiate settlement
     *
     * @param        $redis
     * @param string $channel
     * @param        $count
     * @param bool $shouldInitiate
     */
    protected function dispatchForSettlementInitiate($redis, string $channel, int $count, $shouldInitiate = true)
    {
        // decrement the size by count as those are dispatched to initiate
        $redis->hincrby($this->channelWiseCountKey, $channel, -1 * $count);

        if ($shouldInitiate === true)
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
        }
    }

    /**
     * This method override the parent method so as to delete the message from the queue
     * if the queue job timeout being observed in the Job
     * ref: https://razorpay.slack.com/archives/C015MHZFY49/p1615276985000600
     */
    protected function beforeJobKillCleanUp()
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_CREATE_MESSAGE_DELETE,
            [
                'merchant_id' => $this->merchantId,
            ]);

        $this->delete();

        parent::beforeJobKillCleanUp();
    }
}
