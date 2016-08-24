<?php

namespace RZP\Listeners;

use Illuminate\Foundation\Bus\DispatchesJobs;

use RZP\Constants;
use RZP\Jobs\WebHook;
use RZP\Models\Event;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class ApiEventSubscriber
{
    /* Used to push jobs to queues */
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

    public function __construct()
    {
        $this->app = \App::getFacadeRoot();

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

        $event = ucwords(str_replace('.', ' ', $event));

        $func = 'on'.studly_case($event);

        return $this->$func($params);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('api.*', 'RZP\Listeners\ApiEventSubscriber@onEvent');
    }

    protected function getFiringEvent()
    {
        $event = $this->event->firing();
        $event = substr($event, 4);

        $this->event = $event;

        return $event;
    }

    protected function onPaymentAuthorized($payment)
    {
        $this->prepareAndDispatchPaymentWebhook($payment);
    }

    protected function onPaymentFailed($payment)
    {
        $this->prepareAndDispatchPaymentWebhook($payment);
    }

    protected function prepareAndDispatchPaymentWebhook($payment)
    {
        $webhook = $payment->merchant->webhook;

        $eventFired = $this->event;

        if ($this->isWebhookEnabledForEvent($webhook, $eventFired) === false)
        {
            return;
        }

        $attributes = array(
            Event\Entity::EVENT       => $eventFired,
            Event\Entity::CONTAINS    => Event\Contains::getEntityNamesForEvent($eventFired),
            Event\Entity::CREATED_AT  => $payment->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);

        $payload = array(
            \RZP\Constants\Entity::PAYMENT => [
                'entity' => $payment->toArrayPublic(),
            ],
        );

        $event->setPayload($payload);

        $event->merchant()->associate($payment->merchant);

        $data = array(
            'mode'          => $this->getMode(),
            'event'         => json_encode($event->toArrayPublic()),
            'webhook_id'    => $webhook->getId());

        $this->dispatch(new Webhook($data));
    }

    protected function isWebhookEnabledForEvent($webhook, $event)
    {
        return (($webhook !== null) and
                ($webhook->isActive()) and
                ($webhook->isEventEnabled($this->event)));
    }
}
