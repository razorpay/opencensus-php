<?php

namespace RZP\Listeners;

use Illuminate\Events\Dispatcher;

use App;
use RZP\Constants;
use RZP\Jobs\WebHook;
use RZP\Models\Base;
use RZP\Models\Event;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Jobs\DispatchRouter;
use RZP\Models\Merchant\Webhook\Event as WebhookEvent;

class ApiEventSubscriber extends Base\Core
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

    protected $queue;

    protected $params;

    protected $webhookEnabledForEvent = false;

    /**
     * Events for which other things apart from
     * webhooks also needs to be triggered/done.
     *
     * @var array
     */
    protected static $notWebhookOnlyEvents = [
        WebhookEvent::INVOICE_PAID,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->event = $this->app['events'];
        $this->queue = $this->app['queue'];
    }

    public function getMode()
    {
        return $this->app['rzp.mode'];
    }

    public function onEvent($params)
    {
        $event = $this->getFiringEvent();

        $this->webhookEnabledForEvent = $this->isWebhookEnabledForEvent($params);

        //
        // Doesn't execute the event if
        // - The event's purpose is only webhook
        // - Webhook not enabled for the event
        //
        if ((in_array($event, self::$notWebhookOnlyEvents, true) === false) and
            ($this->webhookEnabledForEvent === false))
        {
            return null;
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

    protected function onInvoicePaid($payment)
    {
        (new Invoice\Core)->setCustomerDetailsFromPaymentIfAbsent($payment);

        if ($this->webhookEnabledForEvent === false)
        {
            return;
        }

        // WebHook specific statements
        $payload = $this->getInvoicePayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionActivated($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionOverdue($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionOnHold($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    // protected function onSubscriptionExpired($subscription)
    // {
    //     $payload = $this->getSubscriptionPayload($subscription);
    //
    //     $this->prepareAndDispatchWebhook($payload);
    // }

    protected function onVpaEdited($vpa)
    {
        $payload = $this->getVpaPayload($vpa);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onP2pCreated($p2p)
    {
        $payload = $this->getP2pPayload($p2p);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onP2pRejected($p2p)
    {
        $payload = $this->getP2pPayload($p2p);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onP2pTransferred($p2p)
    {
        $payload = $this->getP2pPayload($p2p);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function getP2pPayload($p2p)
    {
        $source = $p2p->source;

        $sink = $p2p->sink;

        $partialPayload[Constants\Entity::P2P] = [
            'entity' => $p2p->toArrayPublic()
        ];

        $partialPayload['source'] = [
            'entity' => $source->toArrayPublic()
        ];

        $partialPayload['sink'] = [
            'entity' => $sink->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getSubscriptionPayload($subscription)
    {
        $partialPayload[Constants\Entity::SUBSCRIPTION] = [
            'entity' => $subscription->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getVpaPayload($vpa)
    {
        $customer = $vpa->customer;

        $bankAccount = $vpa->bankAccount;

        $partialPayload[Constants\Entity::VPA] = [
            'entity' => $vpa->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::CUSTOMER] = [
            'entity' => $customer->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::BANK_ACCOUNT] = [
            'entity' => $bankAccount->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getOrderPayload($payment)
    {
        $order = $payment->order;

        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::ORDER] = [
            'entity' => $order->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getInvoicePayload($payment)
    {
        $order = $payment->order;
        $invoice = $order->invoice;

        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::ORDER] = [
            'entity' => $order->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::INVOICE] = [
            'entity' => $invoice->toArrayPublic()
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

        $job = new Webhook($data);

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::WEBHOOK, [$this->event]);
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
        $webhook = $this->repo->webhook->findByMerchant($params->merchant);

        return (($webhook !== null) and
                ($webhook->isActive()) and
                ($webhook->isEventEnabled($this->event)));
    }
}
