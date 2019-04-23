<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Models\FundTransfer\Attempt\BulkRecon;

class AttemptsRecon extends Job
{
    const MAX_RETRY_DELAY   = 300;

    const MAX_RETRY_ATTEMPT = 5;
    /**
     * @var string
     */
    protected $queueConfigKey = 'fund_transfer_recon_update';

    /**
     * @var string
     */
    protected $ftaId;

    public function __construct(string $mode, string $ftaId)
    {
        parent::__construct($mode);

        $this->ftaId = $ftaId;
    }

    /**
     * @throws \Throwable
     */
    public function handle()
    {
        try
        {
            parent::handle();

            //
            // fetch all the fta regardless of who processed it
            // This will only used to reconcile (derive the final state) the fta
            //
            $fta = $this->repoManager
                        ->fund_transfer_attempt
                        ->findByIdWithStatus($this->ftaId, Status::INITIATED, null);

            if ($fta === null)
            {
                $this->traceData(TraceCode::FTA_RECONCILE_SKIPPED);

                return;
            }

            $channel = $fta->getChannel();

            (new BulkRecon([], $channel))->processIndividualEntity($fta);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_RECONCILE_JOB_FAILED,
                [
                    'fta_id' => $this->ftaId
                ]);

            if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
            {
                $this->traceData(TraceCode::FTA_RECONCILIATION_JOB_RELEASED);

                $this->release(self::MAX_RETRY_DELAY);
            }
        }
        finally
        {
            $this->traceData(TraceCode::FTA_RECONCILIATION_JOB_DELETED);

            $this->delete();
        }
    }

    protected function traceData(string $traceCode)
    {
        $this->trace->info(
            $traceCode,
            [
                'fta_id'  => $this->ftaId,
            ]);
    }
}
