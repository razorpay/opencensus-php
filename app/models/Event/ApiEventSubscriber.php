<?php

namespace Models\Event;

use Models\Payment;
use Webhook\Fire;

class ApiEventSubscriber
{
    public function __construct()
    {
        ;
    }

    public function onEvent($event)
    {
        if ($event === Type::PAYMENT_AUTHORIZED)
        {
            $id = $param['id'];

            $payment = (new Payment\Repository)->findOrFail($id);

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

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('api.*', 'ApiEventSubscriber@onEvent');
    }
}