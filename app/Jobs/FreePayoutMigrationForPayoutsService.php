<?php

namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class FreePayoutMigrationForPayoutsService extends Job
{
    protected $queueConfigKey = 'free_payout_migration_to_payouts_service';

    protected $trace;

    protected $action;

    protected $merchantId;

    protected $balanceId;

    public function __construct(string $mode,
                                string $action,
                                string $merchantId,
                                string $balanceId)
    {
        parent::__construct($mode);

        $this->action = $action;

        $this->merchantId = $merchantId;

        $this->balanceId = $balanceId;
    }

    public function handle()
    {
        parent::handle();

        $traceData = [
            'action'      => $this->action,
            'merchant_id' => $this->merchantId,
            'balance_id'  => $this->balanceId,
        ];

        $this->trace->info(
            TraceCode::MIGRATE_FREE_PAYOUT_TO_PAYOUTS_SERVICE_JOB_REQUEST,
            $traceData + [
                'attempt' => $this->attempts(),
            ]);

        try
        {
            $response = (new Payout\Core)->performFreePayoutMigration($this->action,
                                                                      $this->merchantId,
                                                                      $this->balanceId);

            $this->trace->info(
                TraceCode::MIGRATE_FREE_PAYOUT_TO_PAYOUTS_SERVICE_JOB_SUCCESS,
                $traceData + [
                    'migration_response' => $response,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::MIGRATE_FREE_PAYOUT_TO_PAYOUTS_SERVICE_JOB_FAILED,
                $traceData);
        }
        finally
        {
            // In case of any exception alert will be raised.
            // We can inspect reason for failure and can be retried
            // through admin action again. Hence deleting from the queue
            $this->delete();
        }
    }
}
