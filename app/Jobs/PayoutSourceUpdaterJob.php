<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\PayoutLink\Core;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\SourceUpdater;

/***
 * NOTE: In case the Payout Status updates are out of order, the PayoutLink State Machine may fail.
 * In which case either all the status updates will be dropped, or only a subset of them will be actually applied.
 *
 * Class PayoutSourceUpdaterJob
 * @package RZP\Jobs
 */
class PayoutSourceUpdaterJob extends Job
{
    const MAX_RETRIES = 5;

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
        parent::handle();

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
//                (new Core())->pushSlackAlert(TraceCode::PAYOUT_SOURCE_UPDATER_MISMATCH_EXPECTED_STATUS,
//                                             $context);

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

            if ($this->attempts() < self::MAX_RETRIES)
            {
                $this->trace->info(TraceCode::PAYOUT_SOURCE_UPDATER_JOB_RELEASED,
                                   $context);

                $this->release(self::MAX_RETRY_DELAY);
            }
        }
    }
}
