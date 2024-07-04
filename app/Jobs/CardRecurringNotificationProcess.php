<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\CardMandate\CardMandateNotification\Core as notificationCore;


class CardRecurringNotificationProcess extends Job
{
    const QUEUE_NAME_KEY  = 'card_recurring_notification';

    protected $params;

    protected $notificationId;

    protected $orderId;

    protected $queueConfigKey = self::QUEUE_NAME_KEY;

    public $timeout = 3600; // 1 hour

    public function __construct(string $mode, string $notificationId, string $orderId)
    {
        $this->notificationId = $notificationId;

        $this->orderId = $orderId;

        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::CARD_RECURRING_NOTIFICATION_PROCESS_JOB,
            [
                'card_mandate_notification_id' => $this->notificationId,
            ]);

        try {
            $core = new notificationCore();
            $notification = $this->repoManager->card_mandate_notification->findById($this->notificationId);

            $order = $this->repoManager->order->findOrFail($this->orderId);

            $core->processNotification($notification, $order);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CARD_RECURRING_NOTIFICATION_PROCESS_FAILED,
                [
                    'mode' => $this->mode,
                    'notification_id' => $this->notificationId
                ]
            );

            $core->processNotificationFailure($notification);
        }
    }
}
