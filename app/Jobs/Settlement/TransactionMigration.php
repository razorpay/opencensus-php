<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Bucket\Core;

class TransactionMigration extends Job
{
    const MAX_ATTEMPTS = 5;

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
    public $timeout = 1800;

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
            $core = new Core;

            $status = $core->shouldProcessViaNewService($this->merchantId);
            if ($status === false)
            {
                return;
            }

            $details = $core->migrateSettlableTransactions($this->merchantId, $this->opt);

            $this->trace->info(
                TraceCode::SETTLEMENT_TRANSACTION_DETAILS ,
                [
                    'merchant_id' =>  $this->merchantId,
                    'details'     => $details,
                ]);
        }
        catch (\Throwable $e)
        {
            // if the max attempt is not exhausted then release the job for retry
            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(1);
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_MIGRATE_TRANSACTION_TO_NEW_SERVICE,
                [
                    'merchant_id' => $this->merchantId,
                    'opt'         => $this->opt,
                ]
            );
        }
    }
}
