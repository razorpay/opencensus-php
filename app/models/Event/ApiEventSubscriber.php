<?php

namespace Models\Event;

use Constants;
use Models\Event;
use Models\Payment;
use Webhook\Fire;
use Trace\TraceCode;

class ApiEventSubscriber
{
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

        $this->queue = $this->app['queue'];

        $this->trace = $this->app['trace'];
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
        $events->listen('api.*', 'Models\Event\ApiEventSubscriber@onEvent');
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
        $webhook = $payment->merchant->webhook;

        $eventFired = $this->event;

        if ($this->fireWebhookForEvent($webhook, $eventFired) === false)
        {
            return;
        }

        $attributes = array(
            Entity::EVENT       => $eventFired,
            Entity::MERCHANT_ID => $payment->getMerchantId(),
            Entity::CONTAINS    => Contains::getEntityNamesForEvent($eventFired),
            Entity::CREATED_AT  => $payment->getAuthorizeTimestamp(),
        );

        $event = new Event\Entity($attributes);

        $payload = array(
            Constants\Entity::PAYMENT => [
                'data' => $payment->toArrayPublic(),
            ],
        );

        $event->setPayload($payload);

        $event->merchant()->associate($payment->merchant);

        $data = array(
            'mode'          => $this->getMode(),
            'event'         => json_encode($event->toArrayPublic()),
            'webhook_id'    => $webhook->getId());

        $data = json_encode($data);

        $this->queue->push('Models\Merchant\Webhook\Queue', $data);
    }

    protected function fireWebhookForEvent($webhook, $event)
    {
        return (($webhook !== null) and
                ($webhook->isActive()) and
                ($webhook->isEventEnabled($this->event)));
    }
}