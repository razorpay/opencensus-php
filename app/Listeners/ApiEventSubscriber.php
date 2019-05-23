<?php

namespace RZP\Listeners;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Jobs\WebHook;
use RZP\Models\Event;
use RZP\Models\Payout;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\Customer\Token;
use RZP\Models\VirtualAccount;
use RZP\Jobs\Invoice\Job as InvoiceJob;
use RZP\Jobs\SubscriptionPaymentHandler;
use RZP\Models\Merchant\Webhook\Event as WebhookEvent;
use RZP\Models\Merchant\Webhook\Entity as WebhookEntity;
use RZP\Models\Merchant\Webhook\Metric as WebhookMetric;
use RZP\Models\Merchant\AccessMap\Entity as AccessMapEntity;

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

    /**
     * For most events, merchant can be derived from the entity itself.
     * For others, the merchant may be passed in the listener input.
     *
     * @var Merchant\Entity
     */
    protected $listeningMerchant;

    protected $webhookEnabledForEvent = false;

    /**
     * Active merchant webhook, enabled for the current event.
     */
    protected $activeMerchantWebhook  = null;

    /**
     * Active webhooks of applications used by the merchant, enabled for the current event.
     */
    protected $activeAppsWebhooks     = [];

    const MAIN        = 'main';
    const WITH        = 'with';
    const MERCHANT_ID = 'merchant_id';

    /**
     * Events for which other things apart from
     * webhooks also needs to be triggered/done.
     *
     * @var array
     */
    protected static $notWebhookOnlyEvents = [
        WebhookEvent::INVOICE_PARTIALLY_PAID,
        WebhookEvent::INVOICE_PAID,
        WebhookEvent::PAYMENT_AUTHORIZED,
        WebhookEvent::PAYMENT_FAILED,
        WebhookEvent::PAYOUT_PROCESSED,
        WebhookEvent::PAYOUT_REVERSED,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->event = $this->app['events'];
    }

    public function getMode()
    {
        return $this->app['rzp.mode'];
    }

    public function onEvent($event, $params)
    {
        $event = $this->getFiringEvent($event);

        $this->trace->count(WebhookMetric::WEBHOOK_EVENTS_TRIGGERED_TOTAL, compact('event'));

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
            $this->mainEntity  = $params[self::MAIN];
            $this->withPayload = $params[self::WITH] ?? [];

            //
            // Webhooks can be triggered for shared entities,
            // i.e. entities that do not belong to a specific
            // merchant. In this case, merchant will be part of the input.
            //
            if (isset($params[self::MERCHANT_ID]) === true)
            {
                $merchantId = $params[self::MERCHANT_ID];

                $this->listeningMerchant = $this->repo->merchant->findOrFail($merchantId);
            }
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
     * @param  \Illuminate\Events\Dispatcher $events
     *
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

        $merchant = $this->getMerchantFromEntity($payment);

        if ($payment->hasSubscription() === true)
        {
            $paymentPayload = $this->constructPaymentPayloadForSubscriptionNotification($payment);

            SubscriptionPaymentHandler::dispatch($paymentPayload, $this->mode);
        }

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPaymentFailed($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        $merchant = $this->getMerchantFromEntity($payment);

        if ($payment->hasSubscription() === true)
        {
            $paymentPayload = $this->constructPaymentPayloadForSubscriptionNotification($payment);

            SubscriptionPaymentHandler::dispatch($paymentPayload, $this->mode);
        }

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPaymentCaptured($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPaymentDisputeCreated($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPaymentDisputeLost($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPaymentDisputeWon($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPaymentDisputeClosed($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onFundAccountValidationCompleted(FundAccount\Validation\Entity $fundAccountValidation)
    {
        $payload = $this->getFundAccountValidationPayload($fundAccountValidation);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onOrderPaid($payment)
    {
        $payload = $this->getOrderPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onVirtualAccountCredited(Payment\Entity $payment)
    {
        $payload = $this->getVirtualAccountPaymentPayload($payment);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onVirtualAccountCreated(VirtualAccount\Entity $virtualAccount)
    {
        $payload = $this->getVirtualAccountPayload($virtualAccount);

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
        // Pulls customer info from payment and updates invoice's if not set
        (new Invoice\Core)->setCustomerDetailsFromPaymentIfAbsent($payment);

        // Fires a job so in async pdf can be refreshed
        InvoiceJob::dispatch($this->getMode(), InvoiceJob::CAPTURED, $payment->getInvoiceId());

        // Follows web hook related code conditionally, Refer $notWebhookOnlyEvents
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

    protected function onTransactionCreated(Transaction\Entity $txn)
    {
        $payload = $this->getTransactionPayload($txn);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPayoutCreated(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);

        $this->prepareAndDispatchWebhook($payload);
    }

    protected function onPayoutProcessed(Payout\Entity $payout)
    {
        if ($payout->isOfMerchantTransaction() === true)
        {
            (new Transaction\Notifier($payout->transaction, $this->event))->notify();
        }

        if ($this->webhookEnabledForEvent === true)
        {
            $payload = $this->getPayoutPayload($payout);

            $this->prepareAndDispatchWebhook($payload);
        }
    }

    protected function onPayoutQueued(Payout\Entity $payout)
    {
        if ($this->webhookEnabledForEvent === true)
        {
            $payload = $this->getPayoutPayload($payout);

            $this->prepareAndDispatchWebhook($payload);
        }
    }

    protected function onPayoutInitiated(Payout\Entity $payout)
    {
        if ($this->webhookEnabledForEvent === true)
        {
            $payload = $this->getPayoutPayload($payout);

            $this->prepareAndDispatchWebhook($payload);
        }
    }

    protected function onPayoutReversed(Payout\Entity $payout)
    {
        // Todo: Uncomment this once payout_reversed.blade.php file is updated with content.
        // if ($payout->isOfMerchantTransaction() === true)
        // {
        //     (new Transaction\Notifier($payout->transaction, $this->event))->notify();
        // }

        if ($this->webhookEnabledForEvent === true)
        {
            $payload = $this->getPayoutPayload($payout);

            $this->prepareAndDispatchWebhook($payload);
        }
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

    protected function getVirtualAccountPaymentPayload(Payment\Entity $payment)
    {
        $receiver = $payment->receiver;

        $virtualAccount = $receiver->source;

        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic()
        ];

        $virtualAccountArray = $virtualAccount->toArrayPublic();

        //
        // The virtual account array received here will contain
        // all the receivers but we only want that receiver on
        // which the payment is received
        //
        unset($virtualAccountArray[VirtualAccount\Entity::RECEIVERS]);

        $virtualAccountArray[VirtualAccount\Entity::RECEIVERS] = [
            $receiver->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::VIRTUAL_ACCOUNT] = [
            'entity' => $virtualAccountArray,
        ];

        $transfer = null;

        if ($payment->isBankTransfer() === true)
        {
            $transfer = $payment->bankTransfer;
        }
        else if ($payment->isBharatQr() === true)
        {
            $transfer = $payment->bharatQr;
        }
        else
        {
            throw new Exception\LogicException('Invalid method for virtual account');
        }

        $partialPayload[$transfer->getEntity()] = [
            'entity' => $transfer->toArrayPublic(),
        ];

        return $partialPayload;
    }

    protected function getVirtualAccountPayload(VirtualAccount\Entity $virtualAccount)
    {
        $partialPayload[Constants\Entity::VIRTUAL_ACCOUNT] = [
            'entity' => $virtualAccount->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getFundAccountValidationPayload(FundAccount\Validation\Entity $fundAccountValidation)
    {
        $partialPayload['fund_account.validation'] = [
            'entity' => $fundAccountValidation->toArrayPublic()
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

    protected function getTransactionPayload(Transaction\Entity $txn): array
    {
        $payload = [
            Constants\Entity::TRANSACTION => [
                'entity' => $txn->toStatement()->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getPayoutPayload(Payout\Entity $payout): array
    {
        $payload = [
            Constants\Entity::PAYOUT => [
                'entity' => $payout->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getPaymentPayloadWithDispute($payment)
    {
        $partialPayload = $this->getPaymentPayload($payment);

        // Add dispute entity defined in `withPayload`
        $this->addExtraDataToPayload($partialPayload);

        return $partialPayload;
    }

    protected function prepareAndDispatchWebhook(array $payload)
    {
        $merchantWebhook = $this->activeMerchantWebhook;

        // If merchant webhook is active and enabled, then dispatch.
        if ($merchantWebhook !== null)
        {
            $data = $this->getWebhookData($payload, $merchantWebhook);

            $this->dispatchWebhook($data);
        }

        $activeEnabledAppWebhooks = $this->activeAppsWebhooks;

        // Dispatch each active and enabled connected App Webhook
        foreach ($activeEnabledAppWebhooks as $activeEnabledAppWebhook)
        {
            $data = $this->getWebhookData($payload, $activeEnabledAppWebhook);

            $this->dispatchWebhook($data);
        }
    }

    protected function dispatchWebhook(array $data)
    {
        $this->trace->info(TraceCode::WEBHOOK_DISPATCH, $data);

        Webhook::dispatch($data)->using([$this->event]);
    }

    protected function getWebhookData(array $payload, WebhookEntity $webhook): array
    {
        $eventFired = $this->event;
        $entity     = $this->mainEntity;
        $merchant   = $this->getMerchantFromEntity($entity);

        //
        // Send the signed account id of the merchant associated with the entity, along with the payload
        // In case of settlements, $entity->merchant is the the merchant to whom the settlement is processed
        //
        $signedAccountId = Merchant\Account\Entity::getSignedId($entity->merchant->getId());

        $attributes = array(
            Event\Entity::EVENT      => $eventFired,

            //
            // The same event may or may not contain some entities, based on the state.
            // For example, if subscription.pending is fired on an auth failure,
            // the payload will contain only subscription entity not contain `payment` entity.
            // If it's fired on capture failure, it'll contain both subscription and payment
            // entity. For this reason, we cannot have a static list of contains array.
            //
            Event\Entity::ACCOUNT_ID => $signedAccountId,
            Event\Entity::CONTAINS   => array_keys($payload),
            Event\Entity::CREATED_AT => $entity->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);

        $event->setPayload($payload);

        $event->merchant()->associate($merchant);

        $data = [
            'mode'       => $this->getMode(),
            'event'      => json_encode($event->toArrayPublic()),
            'event_name' => $eventFired,
            'webhook_id' => $webhook->getId(),
            // Refer Inferno's eventQueuedAt.
            'queued_at'  => millitime(),
        ];

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

    protected function isWebhookEnabledForEvent(Base\PublicEntity $entity): bool
    {
        $merchant = $this->getMerchantFromEntity($entity);

        $webhook = $this->repo->webhook->findByMerchant($merchant);

        // Check if the merchant has an active webhook for the event
        $enabledForMerchant = $this->isWebhookActiveAndEnabled($webhook);

        if ($enabledForMerchant === true)
        {
            $this->activeMerchantWebhook = $webhook;
        }

        //
        // Check if any of the Partner applications connected to the merchant have an
        // active webhook for the event. If defined, we will eventually send the
        // webhook request to all of these active application webhooks.
        //
        $enabledForApps = $this->checkAndSetWebhooksEnabledForEventForAnyApp();

        return (($enabledForApps or $enabledForMerchant) === true);
    }

    protected function isWebhookActiveAndEnabled($webhook = null): bool
    {
        return (($webhook !== null) and
                ($webhook->isActive() === true) and
                ($webhook->isEventEnabled($this->event)));
    }

    protected function checkAndSetWebhooksEnabledForEventForAnyApp(): bool
    {
        $merchantId = $this->mainEntity->merchant->getId();

        $activeEnabledAppWebhooks = $this->getActiveWebhooksForConnectedApps($merchantId);

        if (count($activeEnabledAppWebhooks) > 0)
        {
            $this->activeAppsWebhooks = $activeEnabledAppWebhooks;

            return true;
        }

        return false;
    }

    /**
     * Gets active webhooks used by al lthe apps used by the merchant whose
     * event is triggering the webhooks. Takes the apps used by the merchant
     * as input.
     *
     * @param string $merchantId
     *
     * @return array $activeEnabledAppWebhooks
     */
    protected function getActiveWebhooksForConnectedApps(string $merchantId)
    {
        // Fetch all applications connected to the current merchant ID
        $appConnections = $this->repo
                               ->merchant_access_map
                               ->fetchMerchantAccessMapsOnEntityType($merchantId, WebhookEntity::APPLICATION);

        if (count($appConnections) === 0)
        {
            return [];
        }

        //
        // If one or more applications are connected, fetch all webhooks that are
        // defined by the applications.
        //
        $appIds = $appConnections->pluck(AccessMapEntity::ENTITY_ID)->all();

        $appWebhooks = $this->repo->webhook->findMultipleByApplicationIds($appIds);

        // Filter and return the list of active application webhooks
        $activeEnabledAppWebhooks = $appWebhooks->filter(function($webhook, $key)
        {
            return ($this->isWebhookActiveAndEnabled($webhook) === true);
        });

        return $activeEnabledAppWebhooks;
    }

    /**
     * Returns the entity's merchant.
     * If the merchant is a linked account, returns the parent merchant.
     *
     * @param Base\PublicEntity $entity
     *
     * @return Merchant\Entity
     */
    protected function getMerchantFromEntity(Base\PublicEntity $entity): Merchant\Entity
    {
        if ($this->listeningMerchant !== null)
        {
            return $this->listeningMerchant;
        }

        if (($entity instanceof Merchant\Account\Entity) === true)
        {
            $merchant = $entity;
        }
        else
        {
            $merchant = $entity->merchant;
        }

        if ($merchant->isLinkedAccount() === true)
        {
            $merchant = $merchant->parent;
        }

        return $merchant;
    }

    protected function constructPaymentPayloadForSubscriptionNotification(Payment\Entity $payment): array
    {
        $payload = $payment->toArrayAdmin();

        $payload['merchant'] = [
            Merchant\Entity::BILLING_LABEL => $payment->merchant->getBillingLabel(),
            Merchant\Entity::WEBSITE       => $payment->merchant->getWebsite(),
            Merchant\Entity::EMAIL         => $payment->merchant->getTransactionReportEmail(),
        ];

        $payload['customer'] = [
            'email' => $payment->customer->getEmail(),
            'phone' => $payment->customer->getContact(),
        ];

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;
            $expiryMonth = str_pad($card->getExpiryMonth(), 2, '0', STR_PAD_LEFT);

            $payload['card'] = [
                'number'  => '**** **** **** ' . $card->getLast4(),
                'expiry'  => $expiryMonth . '/' . $card->getExpiryYear(),
                'network' => $card->getNetworkCode(),
                'color'   => $card->getNetworkColorCode()
            ];
        }

        if ($payment->hasInvoice() === true)
        {
            $payload['invoice'] = [
                Invoice\Entity::BILLING_START => $payment->invoice->getBillingStart(),
                Invoice\Entity::BILLING_END   => $payment->invoice->getBillingEnd()
            ];
        }

        return $payload;
    }
}
