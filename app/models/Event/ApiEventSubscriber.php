<?php

namespace Models\Event;

use Models\Payment;
use Webhook\Fire;

class ApiEventSubscriber
{
    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->event = $app['events'];
    }

    public function onEvent($params)
    {
        $event = $this->getFiringEvent();

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

        return $event;
    }

    protected function onPaymentAuthorized($payment)
    {
        $attributes = array(
            Enttiy::EVENT       => $event,
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

        Fire::fire($entity);
    }
}