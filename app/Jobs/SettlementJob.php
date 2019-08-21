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
    // TODO: change the queue, should have dedicated queue for this
    protected $queueConfigKey = 'settlement_transactions';

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
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::SETTLEMENT_JOB_INIT_FOR_MERCHANT,
                [
                    'merchant_id'       => $this->merchantId,
                    'settlement_bucket' => $this->settlementBucket,
                ]
            );

            $setlResponse = (new SettlementProcessor)->fetchAndProcessTransactionsForSettlement($this->merchantId);

            $response = [
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
            $data = [
                'merchant_id'       => $this->merchantId ,
                'mode'              => $this->mode,
            ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENTS_PROCESS_FAILED_FOR_MERCHANT,
                $data);

            $operation = 'Settlement creation failed for MID:' . $this->merchantId;

            (new SlackNotification)->send($operation, $data, $e);
        }
    }
}
