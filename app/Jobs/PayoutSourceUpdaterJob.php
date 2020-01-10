<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\SourceUpdater;

class PayoutSourceUpdaterJob extends Job
{

    protected $queueConfigKey = 'fund_transfer_recon_update';

    protected $payoutPublicId;

    protected $previousPayoutStatus;

    public function __construct(string $mode, string $payoutPublicId, $previousPayoutStatus)
    {
        parent::__construct($mode);

        $this->payoutPublicId = $payoutPublicId;

        $this->previousPayoutStatus = $previousPayoutStatus;
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $payout = $this->repoManager->payout->findByPublicId($this->payoutPublicId);

            $this->trace->info(
                TraceCode::PAYOUT_SOURCE_UPDATER_JOB,
                [
                    'payout_id'      => $this->payoutPublicId,
                    'current_status' => $payout->getStatus(),
                    'previous_status' => $this->previousPayoutStatus
                ]
            );

            SourceUpdater::handleUpdateFromQueue($payout, $this->previousPayoutStatus);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_SOURCE_UPDATER_JOB_FAILED,
                [
                    'payout_id'       => $this->payoutPublicId,
                    'previous_status' => $this->previousPayoutStatus
                ]);
        }
        finally
        {
            $this->delete();
        }

    }
}
