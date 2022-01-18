<?php

namespace RZP\Events\P2p;

use RZP\Models\P2p\Client;
use RZP\Models\P2p\Client\Config;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeviceCooldownCompleted extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'device.cooldown.completed';
    }

    public function getWebhookPaylaod()
    {
        return;
    }

    public function getNotificationPayload()
    {
        $entity = $this->getEntity();

        $handle = $this->context->getHandle();

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
            'template' => 'sms.p2p.cooldown_completed',
            'sender' => $sender,
            'params' => [
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
