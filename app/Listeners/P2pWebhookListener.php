<?php

namespace RZP\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Jobs;
use RZP\Models\Event;
use RZP\Trace\TraceCode;
use RZP\Models\P2p\Transaction;
use RZP\Models\Merchant\Webhook;

class P2pWebhookListener extends P2pListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        parent::handle($event);

        $webhook = $this->getWebhook();

        if (empty($webhook) === true)
        {
            return;
        }

        $payload = $this->event->getWebhookPaylaod();

        if (empty($payload) === true)
        {
            return;
        }

        $data = $this->getWebhookData($payload, $webhook);

        $this->app['trace']->info(TraceCode::WEBHOOK_DISPATCH, $data);

        Jobs\WebHook::dispatch($data)->using([$this->event->getName()]);
    }

    protected function getWebhookData(array $payload, Webhook\Entity $webhook): array
    {
        $eventFired = $this->event->getName();
        $merchant   = $this->getMerchant();
        $entity     = $this->event->getEntity();

        $attributes = array(
            Event\Entity::EVENT      => $eventFired,
            Event\Entity::CONTAINS   => array_keys($payload),
            Event\Entity::CREATED_AT => $entity->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);

        $event->setPayload($payload);

        $event->merchant()->associate($merchant);

        $data = [
            'mode'       => $this->getMode(),
            'event'      => json_encode($event->toArrayPublic()),
            'event_name' => $eventFired,
            'webhook_id' => $webhook->getId(),
            // Refer Inferno's eventQueuedAt.
            'queued_at'  => millitime(),
        ];

        return $data;
    }
}
