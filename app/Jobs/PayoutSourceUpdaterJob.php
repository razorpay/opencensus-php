<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\SourceUpdater;

class PayoutSourceUpdaterJob extends Job
{
    const MAX_RETIRES = 5;

    const MAX_RETRY_DELAY   = 300;

    protected $payoutPublicId;

    protected $previousPayoutStatus;

    protected $expectedCurrentStatus;

    public function __construct(string $mode, string $payoutPublicId, $previousPayoutStatus, $expectedCurrentStatus)
    {
        parent::__construct($mode);

        $this->payoutPublicId = $payoutPublicId;

        $this->previousPayoutStatus = $previousPayoutStatus;

        $this->expectedCurrentStatus = $expectedCurrentStatus;
    }

    public function handle()
    {
        $context = [
            'payout_id'               => $this->payoutPublicId,
            'previous_status'         => $this->previousPayoutStatus,
            'expected_current_status' => $this->expectedCurrentStatus
        ];

        try
        {
            $payout = $this->repoManager->payout->findByPublicId($this->payoutPublicId);

            $context['current_status'] = $payout->getStatus();

            $this->trace->info(
                TraceCode::PAYOUT_SOURCE_UPDATER_JOB,
                $context
            );

            if ($this->expectedCurrentStatus !== $payout->getStatus())
            {
                // todo, pl add a slack push here
                $this->trace->warning(TraceCode::PAYOUT_SOURCE_UPDATER_MISMATCH_EXPECTED_STATUS,
                                      $context);

                return;
            }
            SourceUpdater::update($payout, $this->previousPayoutStatus);
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

            if($this->attempts() < self::MAX_RETIRES)
            {
                $this->trace->info(TraceCode::PAYOUT_SOURCE_UPDATER_JOB_RELEASED,
                                   $context);

                $this->release(self::MAX_RETRY_DELAY);
            }
        }
        finally
        {
            $this->trace->info(TraceCode::PAYOUT_SOURCE_UPDATER_JOB_RELEASED,
                               $context);

            $this->delete();
        }
    }
}
