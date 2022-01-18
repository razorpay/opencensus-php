<?php

namespace RZP\Events\P2p;

use App;
use RZP\Models\P2p\Client;
use RZP\Models\P2p\Client\Config;
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
        $handle = $this->context->getHandle();

        $entity = $this->getEntity();

        /**
         * @var $client Client\Entity
         */
        $client =  $entity->client($handle);

        $appName = $client->getConfigValue(Config::APP_FULL_NAME);

        $sender  = $client->getConfigValue(Config::SMS_SENDER);

        $smsSignature = $client->getConfigValue(Config::SMS_SIGNATURE);

        $payload = [
            'receiver' => $entity->getFormattedContact(),
            'source'   => "api.{$this->context->getMode()}.p2p",
            'template' => 'sms.p2p.verification_completed',
            'sender'   => $sender,
            'params'   => [
                'app_name'      => $appName,
                'sms_signature' => $smsSignature,
            ],
        ];

        $orgId = $entity->getMerchantOrgId();

        // appending orgId in stork context to be used on stork to select org specific sms gateway.
        if (empty($orgId) === false)
        {
            $payload['stork']['context']['org_id'] = $orgId;
        }

        return $payload;
    }

    public function getReminderPayload()
    {
        return;
    }
}
