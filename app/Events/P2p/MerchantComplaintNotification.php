<?php

namespace RZP\Events\P2p;

use RZP\Models\P2p\Complaint\Entity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * This is the event to send merchant complaint notification
 * Class MandateCompleted
 *
 * @package RZP\Events\P2p
 */
class MerchantComplaintNotification extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'merchant.complaint.notification';
    }

    public function getWebhookPaylaod()
    {
        return $this->getEntity()->toArrayPublic();
    }

    public function getNotificationPayload()
    {
        return;
    }

    public function getReminderPayload()
    {
        return;
    }

    public function postHandle()
    {

    }

    protected function setOriginal(array $original = null)
    {
        $this->original = $original;

        return $this;
    }
}
