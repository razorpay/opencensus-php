<?php

namespace RZP\Listeners;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Events\Dispatcher;

use App;
use RZP\Constants;
use RZP\Jobs\WebHook;
use RZP\Models\Event;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class ApiEventSubscriber
{
    // Used to push jobs to queues
    use DispatchesJobs;

    protected $app;

    /**
     * Event being fired
     * @var string
     */
    protected $event;

    /**
     * Laravel Events instance
     * @var
     */
    protected $events;

    protected $queue;

    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->event = $this->app['events'];
        $this->trace = $this->app['trace'];
        $this->queue = $this->app['queue'];
    }

    public function getMode()
    {
        return $this->app['rzp.mode'];
    }

    public function onEvent($params)
    {
        $event = $this->getFiringEvent();

        $event = str_replace('.', '_', $event);

        $func = 'on' . studly_case($event);

        return $this->$func($params);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('api.*', 'RZP\Listeners\ApiEventSubscriber@onEvent');
    }

    protected function getFiringEvent()
    {
        $event = $this->event->firing();

        // This is being done because the event names start with "api."
        $event = substr($event, 4);

        $this->event = $event;

        return $event;
    }

    protected function onPaymentAuthorized($payment)
    {
        $this->prepareAndDispatchWebhook($payment);
    }

    protected function onPaymentFailed($payment)
    {
        $this->prepareAndDispatchWebhook($payment);
    }

    protected function onOrderPaid($order)
    {
        $this->prepareAndDispatchWebhook($order);
    }

    protected function prepareAndDispatchWebhook($entity)
    {
        $webhook = $entity->merchant->webhook;

        $eventFired = $this->event;

        if ($this->isWebhookEnabledForEvent($webhook) === false)
        {
            return;
        }

        $attributes = array(
            Event\Entity::EVENT       => $eventFired,
            Event\Entity::CONTAINS    => Event\Contains::getEntityNamesForEvent($eventFired),
            Event\Entity::CREATED_AT  => $entity->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);

        $payload = $this->getPayload($entity);

        $event->setPayload($payload);

        $event->merchant()->associate($entity->merchant);

        $data = array(
            'mode'          => $this->getMode(),
            'event'         => json_encode($event->toArrayPublic()),
            'webhook_id'    => $webhook->getId());

        $this->dispatch(new Webhook($data));
    }

    protected function getPayload($entity)
    {
        $entityType = $entity->getEntity();

        $payload = array(
            $entityType => ['entity' => $entity->toArrayPublic()]
        );

        return $payload;
    }

    protected function isWebhookEnabledForEvent($webhook)
    {
        return (($webhook !== null) and
                ($webhook->isActive()) and
                ($webhook->isEventEnabled($this->event)));
    }
}
