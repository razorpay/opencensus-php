<?php

namespace RZP\Listeners;

use Illuminate\Events\Dispatcher;

use App;
use RZP\Constants;
use RZP\Jobs\WebHook;
use RZP\Jobs\DispatchRouter;
use RZP\Models\Base;
use RZP\Models\Customer\Token;
use RZP\Models\Event;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
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

    /**
     * For invoice.paid, the mainEntity would consist of the invoice entity.
     * The data from mainEntity is used to get updated_at and merchant.
     *
     * @var Base\PublicEntity
     */
    protected $mainEntity;

    /**
     * For invoice.paid, the withPayload can consist of payment and order.
     * These are like helper entities or extra information for the merchant.
     *
     * @var array
     */
    protected $withPayload;

    protected $webhookEnabledForEvent = false;

    const MAIN = 'main';
    const WITH = 'with';

    /**
     * Events for which other things apart from
     * webhooks also needs to be triggered/done.
     *
     * @var array
     */
    protected static $notWebhookOnlyEvents = [
        WebhookEvent::INVOICE_PARTIALLY_PAID,
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

    public function onEvent($event, $params)
    {
        $event = $this->getFiringEvent($event);

        //
        // sequential_array check is present here only
        // to ensure backward compatibility.
        //
        if (is_sequential_array($params) === true)
        {
            $this->mainEntity = $params[0];
        }
        else
        {
            $this->mainEntity = $params[self::MAIN];
            $this->withPayload = $params[self::WITH] ?? [];
        }

        //
        // Some events can send multiple entities in the array.
        // The first entity should be the main entity and the others
        // should be helper entities only.
        // For example, if invoice events sends 3 entities,
        // the first one should be of invoice and the second
        // and third should be of payment and order.
        //
        // We use allParams to construct the payload.
        // We use the first param to get the associated merchant,
        // updated_at and other things like that.
        //

        $this->webhookEnabledForEvent = $this->isWebhookEnabledForEvent($this->mainEntity);

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

        $event = str_replace('.', '_', $event);

        $func = 'on' . studly_case($event);

        return $this->$func($this->mainEntity);
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

    protected function getFiringEvent($event)
    {
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

    protected function onPaymentCaptured($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onOrderPaid($payment)
    {
        $payload = $this->getOrderPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onInvoicePartiallyPaid($payment)
    {
        //
        // It is safe to just call the other method which gets called with
        // invoice.paid event. The web hook payload is same in both event (it's
        // invoice, order, payment entities), just the event name differs.
        //
        $this->onInvoicePaid($payment);
    }

    protected function onInvoicePaid($payment)
    {
        //
        // Other than firing web hook in this case, we also update invoice's copy
        // of customer details if that is empty, with payment's attributes.
        //
        // Refer $notWebhookOnlyEvents also.
        //
        (new Invoice\Core)->setCustomerDetailsFromPaymentIfAbsent($payment);

        if ($this->webhookEnabledForEvent === false)
        {
            return;
        }

        $payload = $this->getInvoicePayloadWithPayment($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onInvoiceExpired($invoice)
    {
        $payload = $this->getInvoicePayload($invoice);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionActivated($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionPending($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionHalted($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionCancelled($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionCompleted($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSubscriptionCharged($subscription)
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

    protected function onTokenConfirmed($token)
    {
        $payload = $this->getTokenPayload($token);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onTokenRejected($token)
    {
        $payload = $this->getTokenPayload($token);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onSettlementProcessed($settlement)
    {
        $payload = $this->getSettlementPayload($settlement);

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

    protected function getTokenPayload(Token\Entity $token)
    {
        $payload = [
            Constants\Entity::TOKEN => [
                'entity' => $token->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getSubscriptionPayload($subscription)
    {
        $partialPayload[Constants\Entity::SUBSCRIPTION] = [
            'entity' => $subscription->toArrayPublic()
        ];

        $this->addExtraDataToPayload($partialPayload);

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

    protected function getInvoicePayload(Invoice\Entity $invoice)
    {
        $payload = [
            Constants\Entity::INVOICE => [
                'entity' => $invoice->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getInvoicePayloadWithPayment($payment)
    {
        $order   = $payment->order;
        $invoice = $order->invoice;

        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic(),
        ];

        $partialPayload[Constants\Entity::ORDER] = [
            'entity' => $order->toArrayPublic(),
        ];

        $partialPayload[Constants\Entity::INVOICE] = [
            'entity' => $invoice->toArrayPublic(),
        ];

        return $partialPayload;
    }

    protected function getPaymentPayload($payment)
    {
        $payload = [
            Constants\Entity::PAYMENT => [
                'entity' => $payment->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getSettlementPayload($settlement)
    {
        $payload = [
            Constants\Entity::SETTLEMENT => [
                'entity' => $settlement->toArrayPublic(),
            ],
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
        $entity = $this->mainEntity;
        $merchant = $this->getMerchantFromEntity($entity);
        $webhook = $merchant->webhook;

        // Send the signed account id of the merchant associated with the entity, along with the payload
        // In case of settlements, $entity->merchant is the the merchant to whom the settlement is processed
        $signedAccountId = Merchant\AccountEntity::getSignedId($entity->merchant->getId());

        $attributes = array(
            Event\Entity::EVENT       => $eventFired,
            //
            // The same event may or may not contain some entities, based on the state.
            // For example, if subscription.pending is fired on an auth failure,
            // the payload will contain only subscription entity not contain `payment` entity.
            // If it's fired on capture failure, it'll contain both subscription and payment
            // entity. For this reason, we cannot have a static list of contains array.
            //
            Event\Entity::CONTAINS    => array_keys($payload),
            Event\Entity::ACCOUNT_ID  => $signedAccountId,
            Event\Entity::CREATED_AT  => $entity->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);

        $event->setPayload($payload);

        $event->merchant()->associate($merchant);

        $data = array(
            'mode'          => $this->getMode(),
            'event'         => json_encode($event->toArrayPublic()),
            'webhook_id'    => $webhook->getId()
        );

        return $data;
    }

    protected function addExtraDataToPayload(array & $partialPayload)
    {
        foreach ($this->withPayload as $withKey => $withValue)
        {
            //
            // This check is required since sometimes, the entity could be null.
            // In those cases, we don't want to send the entity key at all
            // in the webhook payload.
            //
            if ($withValue instanceof Base\PublicEntity)
            {
                $partialPayload[$withKey] = [
                    'entity' => $withValue->toArrayPublic()
                ];
            }
        }
    }

    protected function isWebhookEnabledForEvent(Base\PublicEntity $entity)
    {
        $merchant = $this->getMerchantFromEntity($entity);

        $webhook = $this->repo->webhook->findByMerchant($merchant);

        return (($webhook !== null) and
                ($webhook->isActive()) and
                ($webhook->isEventEnabled($this->event)));
    }

    /**
     * Returns the settlement entity's merchant.
     * If the merchant is a linked account, returns the parent merchant.
     *
     * @param Base\PublicEntity $entity
     *
     * @return Merchant\Entity
     */
    protected function getMerchantFromEntity(Base\PublicEntity $entity): Merchant\Entity
    {
        $merchant = $entity->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            $merchant = $merchant->parent;
        }

        return $merchant;
    }
}
