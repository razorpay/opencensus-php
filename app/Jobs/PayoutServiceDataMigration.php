<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\Core;
use Razorpay\Trace\Logger as Trace;

class PayoutServiceDataMigration extends Job
{
    const BALANCE_ID       = 'balance_id';
    const MERCHANT_ID      = 'merchant_id';

    const MAX_RETRY_ATTEMPT = 5;

    const MAX_RETRY_DELAY = 10;

    const MAX_ATTEMPTS_FOR_DATA_MIGRATION = 10;

    /**
     * @var string
     */
    protected $queueConfigKey = 'payout_service_data_migration';

    /**
     * @var array
     */
    protected $params;

    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::PAYOUTS_DATA_MIGRATION_JOB_INIT,
                $this->params
            );

            // Returns completed if whole data (within the kept constraints) is migrated.
            $response = (new Core)->processDataMigration($this->params);

            if ($response === 'completed')
            {
                $this->trace->info(
                    TraceCode::PAYOUTS_DATA_MIGRATION_JOB_DELETE,
                    $this->params);

                $this->delete();
            }
            else
            {
                $this->trace->info(
                    TraceCode::PAYOUTS_DATA_MIGRATION_JOB_RELEASE,
                    $this->params);

                // release back into the queue if response is not 'completed'
                // i.e. there is still data migration is incomplete.
                $this->release(self::MAX_RETRY_DELAY);
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUTS_DATA_MIGRATION_EXCEPTION,
                $this->params);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_ATTEMPTS_FOR_DATA_MIGRATION)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_DATA_MIGRATION_JOB_RELEASE,
                $this->params);

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            $this->trace->info(
                TraceCode::PAYOUTS_DATA_MIGRATION_JOB_DELETE,
                $this->params);

            $this->delete();
        }
    }
}
