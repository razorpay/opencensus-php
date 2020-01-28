<?php

namespace RZP\Events\P2p;

use App;
use RZP\Models\P2p\Transaction\Entity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use RZP\Models\P2p\Base\Libraries\Context;

class DeviceVerificationCompleted extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.verification.completed';
    }

    public function getWebhookPaylaod()
    {
        return $this->entity->toArrayPartner();
    }

    public function getNotificationPayload()
    {
        $entity = $this->getEntity();

        $appName = $entity->getAppFullName();
        $sender  = $entity->getSmsSender();

        return [
            'receiver' => $entity->getFormattedContact(),
            'source'   => "api.{$this->context->getMode()}.p2p",
            'template' => 'sms.p2p.verification_completed',
            'sender'   => $sender,
            'params'   => [
                'app_name'      => $appName,
            ],
        ];
    }

    public function getReminderPayload()
    {
        return;
    }
}
