<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Notification\Core as notificationCore;


class UpiAutopayNotificationProcess extends Job
{
    const QUEUE_NAME_KEY  = 'upi_autopay_notification';

    protected $params;

    protected $notificationId;

    protected $queueConfigKey = self::QUEUE_NAME_KEY;

    public $timeout = 3600; // 1 hour

    public function __construct(string $mode, string $notificationId)
    {
        $this->notificationId = $notificationId;

        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::UPI_RECURRING_NOTIFICATION_PROCESS_JOB,
            [
                'notification_id' => $this->notificationId,
            ]);

        $core = new notificationCore();
        $notification = $this->repoManager->notification->findById($this->notificationId);
        try {


            $core->processNotification($notification);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_RECURRING_NOTIFICATION_PROCESS_FAILED,
                [
                    'mode' => $this->mode,
                    'notification_id' => $this->notificationId
                ]
            );

            // do we need to upate status of notification to failed and send webhook
            $core->processNotificationGatewayFailure($notification);
        }
    }
}
