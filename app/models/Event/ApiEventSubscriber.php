<?php

namespace Models\Event;

use Models\Payment;
use Webhook\Fire;

class ApiEventSubscriber
{
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
        $app = \App::getFacadeRoot();

        $this->event = $app['events'];

        $this->inferno = $app['webhook.inferno'];
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

        if ($this->fireWebhookForEvent($webhook, $this->event) === false)
        {
            return;
        }

        $attributes = array(
            Entity::EVENT       => $event,
            Entity::URL         => $webhook->getUrl(),
            Entity::MERCHANT_ID => $payment->getMerchantId(),
            Entity::CONTAINS    => Contains::getEntityNamesForEvent($event),
            Entity::CREATED_AT  => $payment->getAuthorizeTimestamp(),
        );

        $entity = Event\Entity::create($attributes);

        $payload = array(
            Constants\Entity::PAYMENT => [
                'entity' => $payment->toArrayPublic(),
            ],
        );

        $entity->setPayload($payload);

        $entity>merchant()->associate($payment->merchant);

        $this->inferno->fire($entity, $webhook);
    }

    protected function fireWebhookForEvent($webhook, $event)
    {
        return (($webhook !== null) and
                ($webhook->isActive()) and
                ($webhook->isEventEnabled($this->event)));
    }
}