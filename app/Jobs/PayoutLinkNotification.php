<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\PayoutLink\Notifications\Factory;

/***
 * There are multiple type of notifications for payoutlinks, as follows
 * 1. Send Otp Email/Sms
 * 2. Send Payout Link to customer on SMS/EMAIL
 * 3. Send Payout Link Failure on SMS/EMAIL
 * 4. Send Payout Link Success on SMS/EMAIL
 */
class PayoutLinkNotification extends Job
{
    const MAX_RETRIES = 5;

    const MAX_RETRY_DELAY   = 300;

    protected $payoutLinkPublicId;

    protected $notificationType;

    public function __construct(string $mode, string $notificationType, string $payoutLinkPublicId)
    {
        parent::__construct($mode);

        $this->notificationType = $notificationType;

        $this->payoutLinkPublicId = $payoutLinkPublicId;
    }

    public function handle()
    {
        parent::handle();

        $context = [
            'payout_link_id'    => $this->payoutLinkPublicId,
            'notification_type' => $this->notificationType,
            'attempt_count'     => $this->attempts()
        ];

        try
        {
            $payoutLink = $this->repoManager->payout_link->findByPublicId($this->payoutLinkPublicId);

            $notifier = Factory::getNotifier($this->notificationType, $payoutLink);

            $notifier->notify();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_LINK_NOTIFICATION_JOB_FAILED,
                $context);

            if ($this->attempts() < self::MAX_RETRIES)
            {
                $this->trace->info(TraceCode::PAYOUT_LINK_NOTIFICATION_JOB_RELEASED,
                                   $context);

                $this->release(self::MAX_RETRY_DELAY);
            }
        }
    }
}
