<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Bucket\Core;

class TransactionMigrationBatch extends Job
{
    const MUTEX_RESOURCE = 'SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_%s_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 3600;
    /**
     * @var string
     */
    protected $queueConfigKey = 'settlement_initiate';

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var array
     */
    protected $opt;

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 3600;

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
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BEGIN,
                [
                    'merchant_id' => $this->merchantId,
                    'options'     => $this->opt,
                ]);

            $startTime = microtime(true);

            $core = new Core;

            $status = $core->shouldProcessViaNewService($this->merchantId);

            if ($status === false)
            {
                return;
            }

            $resource = sprintf(self::MUTEX_RESOURCE, $this->merchantId, $this->opt['balance_type'], $this->mode);

            $details = $this->mutex->acquireAndRelease(
                $resource,
                function () use($core)
                {
                    return $core->fetchAndEnqueueSettlableTransactionsBatch($this->mode, $this->merchantId, $this->opt);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);

            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_ENQUEUE_SUCCESS,
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
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_ENQUEUE_FAILURE,
                [
                    'merchant_id' => $this->merchantId,
                    'opt'         => $this->opt,
                ]
            );
        }
    }

    protected function beforeJobKillCleanUp()
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_JOB_TIMEOUT,
            [
                'merchant_id' => $this->merchantId,
                'options'     => $this->opt,
            ]);

        $this->delete();

        parent::beforeJobKillCleanUp();
    }
}
