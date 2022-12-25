<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Jitendra\Lqext\TransactionAware;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

/***
 * NOTE: In case the Payout Status updates are out of order, the PayoutLink State Machine may fail.
 * In which case either all the status updates will be dropped, or only a subset of them will be actually applied.
 *
 * Class PayoutSourceUpdaterJob
 * @package RZP\Jobs
 */
class PayoutSourceUpdaterJob extends Job
{
    use TransactionAware;

    const MAX_RETRIES = 5;

    const MAX_RETRY_DELAY   = 300;

    protected $queueConfigKey = 'payout_source_updater';

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
            try
            {
                $payout = $this->repoManager->payout->findByPublicId($this->payoutPublicId);

                if ($payout->getIsPayoutService() === true)
                {
                    PayoutEntity::verifyIdAndStripSign($this->payoutPublicId);

                    $payout = (new PayoutCore())->getAPIModelPayoutFromPayoutService($this->payoutPublicId);
                }
            }
            catch (\Throwable $exception)
            {
                PayoutEntity::verifyIdAndStripSign($this->payoutPublicId);

                $payout = (new PayoutCore())->getAPIModelPayoutFromPayoutService($this->payoutPublicId);

                if (empty($payout) === true)
                {
                    throw $exception;
                }
            }

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

            SourceUpdater::update($payout, $this->previousPayoutStatus, $this->mode);
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
