<?php

namespace RZP\Events\P2p;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VpaDeleted extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.vpa.deleted';
    }

    public function getWebhookPaylaod()
    {
        return $this->entity->toArrayPartner();
    }

    public function getNotificationPayload()
    {
        return;
    }

    public function getReminderPayload()
    {
        return;
    }
}
