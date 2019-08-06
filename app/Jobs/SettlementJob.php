<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Settlement\Processor as SettlementProcessor;

class SettlementJob extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'settlement_transactions';

    /**
     * @var array
     */
    protected $channel;

    /**
     * @var array
     */
    protected $merchantId;

    /**
     * @var array
     */
    protected $transactionIds;

    /**
     * Here, we fetch merchantId and their corresponding unsettled transactionIds.
     *
     * @param string $mode
     * @param string $channel
     * @param string $merchantId
     */
    public function __construct(string $mode, string $channel, string $merchantId)
    {
        parent::__construct($mode);

        $this->channel        = $channel;

        $this->merchantId     = $merchantId;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::SETTLEMENT_JOB_INIT_FOR_MERCHANT,
                [
                    'channel'     => $this->channel,
                    'merchant_id' => $this->merchantId,
                ]
            );

            $setlResponse = (new SettlementProcessor)->fetchAndProcessTransactionsForSettlement(
                $this->channel,
                $this->merchantId
            );

            $response = [
                'channel'       => $this->channel,
                'merchant_id'   => $this->merchantId,
                'setl_count'    => $setlResponse['settlement_count'],
                'txnCount'      => $setlResponse['txn_count'],
                'attempt_count' => $setlResponse['attempt_count'],
                'mode'          => $this->mode,
            ];

            $this->trace->info(
                TraceCode::SETTLEMENT_ATTEMPT_ENTITIES_CREATED_FOR_MERCHANT,
                $response);
        }
        catch (\Throwable $e)
        {
            $transactionCount = count($this->transactionIds);

            $data = [
                'channel'           => $this->channel,
                'merchant_id'       => $this->merchantId ,
                'transaction_count' => $transactionCount,
                'mode'              => $this->mode,
            ];

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::SETTLEMENTS_PROCESS_FAILED_FOR_MERCHANT,
                $data);

            $operation = 'Settlement creation failed';

            (new SlackNotification)->send($operation, $data, null, $transactionCount);
        }
    }
}
