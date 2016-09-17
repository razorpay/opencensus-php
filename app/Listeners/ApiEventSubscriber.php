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

    protected $params;

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

        if ($this->isWebhookEnabledForEvent($params) === false)
        {
            return;
        }

        $this->params = $params;

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
        $payload = $this->getPaymentPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPaymentFailed($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onOrderPaid($payment)
    {
        $payload = $this->getOrderPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function getOrderPayload($payment)
    {
        $order = $payment->order;

        $partialPayload = $this->getPaymentPayload($payment);

        $partialPayload[Constants\Entity::ORDER] = [
            'entity' => $order->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getPaymentPayload($payment)
    {
        $payload = [
            Constants\Entity::PAYMENT => [
                'entity' => $payment->toArrayPublic()
            ]
        ];

        return $payload;
    }

    protected function prepareAndDispatchWebhook(array $payload)
    {
        $data = $this->getWebhookData($payload);

        $this->dispatch(new Webhook($data));
    }

    protected function getWebhookData($payload)
    {
        $eventFired = $this->event;
        $entity = $this->params;
        $webhook = $entity->merchant->webhook;

        $attributes = array(
            Event\Entity::EVENT       => $eventFired,
            Event\Entity::CONTAINS    => Event\Contains::getEntityNamesForEvent($eventFired),
            Event\Entity::CREATED_AT  => $entity->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);

        $event->setPayload($payload);

        $event->merchant()->associate($entity->merchant);

        $data = array(
            'mode'          => $this->getMode(),
            'event'         => json_encode($event->toArrayPublic()),
            'webhook_id'    => $webhook->getId()
        );

        return $data;
    }

    protected function isWebhookEnabledForEvent($params)
    {
        $webhook = $params->merchant->webhook;

        return (($webhook !== null) and
                ($webhook->isActive()) and
                ($webhook->isEventEnabled($this->event)));
    }
}
