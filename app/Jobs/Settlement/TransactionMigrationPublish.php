<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Bucket\Core;

class TransactionMigrationPublish extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'settlement_create';

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var array
     */
    protected $opt;

    /**
     * @param string $mode
     * @param string $merchantId
     * @param array $opt
     */
    public function __construct(string $mode, string $merchantId, array $opt)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->opt        = $opt;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_PUBLISH_BEGIN,
                [
                    'merchant_id' => $this->merchantId,
                ]);

            $startTime = microtime(true);

            $core = new Core;

            $status = $core->shouldProcessViaNewService($this->merchantId);

            if ($status === false)
            {
                return;
            }

            $details = $core->migrateSettlableTransactionsBatch($this->merchantId, $this->opt);

            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_PUBLISH_SUCCESS,
                [
                    'merchant_id' =>  $this->merchantId,
                    'details'     => $details,
                    'time_taken'  => microtime(true) - $startTime,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_PUBLISH_FAILURE,
                [
                    'merchant_id' => $this->merchantId,
                    'opt'         => $this->opt,
                ]
            );
        }
    }

    protected function beforeJobKillCleanUp()
    {
        unset($this->opt['transaction_ids']);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_PUBLISH_JOB_TIMEOUT,
            [
                'merchant_id' => $this->merchantId,
                'options'     => $this->opt,
            ]);

        $this->delete();

        parent::beforeJobKillCleanUp();
    }

}