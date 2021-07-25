<?php

namespace RZP\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Jobs;
use RZP\Models\Event;
use RZP\Trace\TraceCode;
use RZP\Models\P2p\Transaction;
use RZP\Models\Merchant\WebhookV2\Stork;

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

        $eventPayload = $this->event->getWebhookPaylaod();

        if (empty($eventPayload) === true)
        {
            $this->event->postHandle();

            return;
        }

        // Prepares Event\Entity.
        $eventAttrs = [
            Event\Entity::EVENT      => $this->event->getName(),
            Event\Entity::CONTAINS   => array_keys($eventPayload),
            Event\Entity::CREATED_AT => $this->event->getEntity()->getUpdatedAt(),
        ];
        $event = new Event\Entity($eventAttrs);
        $event->generateId();
        $event->setPayload($eventPayload);
        $event->merchant()->associate($this->getMerchant());

        // Invokes fail safe stork's processor on event.
        (new Stork($this->getMode()))->processEventSafe($event);

        $this->event->postHandle();
    }
}
