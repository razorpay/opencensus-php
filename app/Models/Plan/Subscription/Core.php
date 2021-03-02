<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Plan;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Currency\Currency;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Jobs\Plan\ChargeSubscription;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    /**
     * Lock wait timeout for acquiring
     * Since this makes auth and capture requests,
     * the timeout is set to 60*2
     */
    const MUTEX_LOCK_TIMEOUT = 120;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * @param array                 $input
     * @param Plan\Entity           $plan
     * @param Customer\Entity|null  $customer This is not type hinted because customer can be null
     *                                        also, in case the merchant wants to follow global
     *                                        customer flow.
     *
     * @return Entity
     */
    public function create(array $input, Plan\Entity $plan, Customer\Entity $customer = null): Entity
    {
        $this->trace->info(TraceCode::SUBSCRIPTION_CREATE_REQUEST, $input);

        $subscription = (new Creator)->create($input, $plan, $customer);

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CREATED,
            $subscription->toArrayPublic());

        return $subscription;
    }

    public function retry(Entity $subscription, array $options = [])
    {
        $errorStatus = $subscription->getErrorStatus();

        $invoice = $this->repo->invoice->fetchIssuedAndNotHaltedInvoiceForSubscription($subscription);

        if ($errorStatus === Status::AUTH_FAILURE)
        {
            $this->charge($subscription, $invoice, $options);
        }
        else if ($errorStatus === Status::CAPTURE_FAILURE)
        {
            $this->retryCapture($subscription, $invoice);
        }
        else
        {
            throw new LogicException(
                'Invalid status sent to retry subscription',
                null,
                [
                    'subscription_id' => $subscription->getId(),
                    'invoice_id'      => $invoice->getId(),
                ]);
        }
    }

    public function expireSubscription(Entity $subscription)
    {
        //
        // This is required because all the subscriptions are retrieved in bulk.
        // By the time it's this subscription's turn to expired, it's possible
        // that the subscription's status is changed.
        //
        $subscription = $subscription->reload();

        if (($subscription->getStatus() !== Status::CREATED) or
            ($subscription->getStartAt() === null))
        {
            throw new LogicException(
                'Subscription should have been in created state / start_at should not have been set',
                null,
                [
                    'subscription_id' => $subscription->getId(),
                    'status'          => $subscription->getStatus(),
                    'start_at'        => $subscription->getStartAt()
                ]);
        }

        $subscription->setStatus(Status::EXPIRED);

        $this->repo->saveOrFail($subscription);
    }

    public function testCharge(Entity $subscription, $input)
    {
        $this->trace->info(
            TraceCode::SUBSCRIPTION_TEST_CHARGE_REQUEST,
            [
                'subscription_id' => $subscription->getId(),
                'status'          => $subscription->getStatus(),
                'input'           => $input,
            ]);

        if ($this->mode !== Constants\Mode::TEST)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_IN_LIVE,
                null,
                [
                    'operation'         => 'subscription_charge',
                    'subscription_id'   => $subscription->getId(),
                ]);
        }

        $subscription->getValidator()->validateInput('manual_test_charge', $input);

        $input['queue'] = false;

        $subscription->getValidator()->validateTestSubscriptionChargeable();

        //
        // If subscription is in pending state, the only charge the merchant can attempt
        // here is a retry charge. This flow thus simulates the work of the retry cron.
        // For any other (valid) status, we actually create a new invoice and update the
        // subscription, thus simulating the work of the charge cron.
        //
        if ($subscription->getStatus() === Status::PENDING)
        {
            $this->retry($subscription, $input);
        }
        else
        {
            (new Biller)->createInvoiceAndCharge($subscription, $input);
        }

        // Subscription is charged by passing a payload of reference ids
        // to a helper class (Charge). We use a payload, because for cron
        // charges, we queue the job. We don't for manual though, so
        // reloading at this stage ensures that updated values are returned.
        $this->repo->reload($subscription);

        return $subscription;
    }

    /**
     * Subscription need not be updated if it's in created or activated state.
     * That flow would be taken care by the normal subscription capture flow.
     *
     * Only if it's in halted state with capture_failure as error, we need to
     * explicitly update the subscription. This is because, this capture would
     * have been an explicit call and not via normal subscription flow.
     *
     * @param Entity $subscription
     * @param Payment\Entity $capturedPayment
     * @return bool
     */
    public function shouldUpdateSubscriptionOnCapture(Entity $subscription, Payment\Entity $capturedPayment): bool
    {
        $status = $subscription->getStatus();
        $errorStatus = $subscription->getErrorStatus();

        if ($status !== Status::HALTED)
        {
            return false;
        }

        if ($errorStatus === Status::CAPTURE_FAILURE)
        {
            return true;
        }
        else
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_STATE_UNEXPECTED,
                [
                    'payment_id'        => $capturedPayment->getId(),
                    'subscription_id'   => $subscription->getId(),
                    'status'            => $subscription->getStatus(),
                    'error_status'      => $subscription->getErrorStatus(),
                ]);

            return false;
        }
    }

    /**
     * Get subscription data for the checkout preferences route
     *
     * @param Entity $subscription
     * @param bool                $cardChange
     *
     * @return array
     */
    public function getFormattedSubscriptionData(
        Entity $subscription,
        bool $cardChange): array
    {
        $authAmount = $this->getAuthTransactionAmount($subscription, $cardChange);

        return [
            'amount'    => $authAmount,
            'type'      => $subscription->getType(),
            'currency'  => $subscription->plan->item->currency,
        ];
    }

    /**
     * addon_amount  | start_at | charge_amount
     * ----------------------------------------------------------
     * yes            | no       | addon_amount + plan_amount
     * no             | yes      | default_auth_amount (5rs)
     * yes            | yes      | addon_amount
     * no             | no       | plan_amount
     *
     * The above amount is taken care of when we create an invoice
     * and hence not doing those checks here.
     *
     * @param Entity $subscription
     *
     * @param bool   $cardChange
     *
     * @return int
     * @throws BadRequestException
     */
    public function getAuthTransactionAmount(Entity $subscription, bool $cardChange = false): int
    {
        //
        // Currently, we allow a 2FA txn to be done only if
        // it's a new subscription or if the card needs to be
        // changed because subscription is in pending or in
        // halted state.
        // Going forward, we can change this to allow change
        // of card even if there's no issue with the current
        // card and the subscription is in active state.
        //

        if ($cardChange === true)
        {
            $authAmount = $this->getAuthTransactionAmountForCardChange($subscription);
        }
        else if ($subscription->isCreated() === true)
        {
            $authAmount = $this->getAuthTransactionAmountForNewSubscription($subscription);
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_2FA_NOT_ALLOWED,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'status'            => $subscription->getStatus(),
                    'error_status'      => $subscription->getErrorStatus(),
                ]);
        }

        return $authAmount;
    }

    public function getAuthTransactionAmountForCardChange(Entity $subscription): int
    {
        if ($subscription->isPending() === true)
        {
            $invoice = $this->repo->invoice->fetchLatestInvoiceOfPendingSubscription($subscription);

            return $invoice->getAmount();
        }
        else
        {
            if ($subscription->plan->item->getCurrency() === Currency::INR)
            {
                return Entity::DEFAULT_AUTH_AMOUNT;
            }

            $currencyDetails = Currency::getDetails();

            return $currencyDetails[$subscription->plan->item->getCurrency()]['min_auth_value'];
        }
    }

    public function fireWebhookForStatusUpdate(Entity $subscription, string $status, Payment\Entity $payment = null)
    {
        if (array_key_exists($status, Status::$webhookStatuses) === false)
        {
            return;
        }

        //
        // TODO: This should go in some class where every
        // webhook passes through. Even in tests.
        // This is applicable for all webhooks and
        // not just subscription webhooks.
        // An issue in API has been created for this.
        //
        if ($this->repo->isTransactionActive() === true)
        {
            throw new LogicException(
                'Webhook fired inside a transaction',
                ErrorCode::SERVER_ERROR_WEBHOOK_IN_TRANSACTION,
                [
                    'subscription_id'   => $subscription->getId(),
                    'status'            => $status,
                    'method'            => $payment->getMethod()
                ]);
        }

        $event = Status::$webhookStatuses[$status];

        $eventPayload = [
            ApiEventSubscriber::MAIN => $subscription,
        ];

        if ($payment !== null)
        {
            $eventPayload[ApiEventSubscriber::WITH] = [Constants\Entity::PAYMENT => $payment];
        }

        $this->app['events']->dispatch('api.' . $event, $eventPayload);
    }

    public function eventSubscriptionCharged(Entity $subscription, Payment\Entity $payment)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $subscription,
            ApiEventSubscriber::WITH => [
                Constants\Entity::PAYMENT => $payment,
            ]
        ];

        $this->app['events']->dispatch('api.subscription.charged', $eventPayload);
    }

    /**
     * This block is not in redis lock, because fireCharge is anyway
     * in redis lock. This block by itself doesn't do anything much,
     * so it's not really required. Also, currently, since we are
     * not using queues, we'll end up having two redis locks on the
     * same resource. This will fail. Even after we start using queues,
     * fireCharge will handle if there's any change in status and stuff.
     *
     * @param Entity         $subscription
     * @param Invoice\Entity $invoice
     * @param array          $options   List of options for use by merchant, that alter the flow of charge.
     *                                  - manual: Leaves auth_attempts, pending status unchanged
     *                                  - queue: Charges in queue, rather than in sync
     *                                  - success: For test charge, allows testing failures
     *
     * @return bool
     * @throws LogicException
     */
    public function charge(Entity $subscription, Invoice\Entity $invoice, array $options = [])
    {
        $recurringPayload = $this->constructRecurringPayload($subscription, $invoice, $options);

        $queuePayload = [
            'recurring_payload' => $recurringPayload,
            'subscription_id'   => $subscription->getId(),
            'invoice_id'        => $invoice->getId(),
            // This would almost always be rzp_{mode},since it will be
            // run via cron. We actually need the mode here. But basicauth
            // functions mostly work on the key. Hence, sending the key
            // across rather than the mode.
            'key_id'            => $this->app['basicauth']->getPublicKey(),
            'manual'            => boolval($options['manual'] ?? false),
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CHARGE_QUEUE_PAYLOAD_SENT,
            $queuePayload);

        //
        // If the subscription has not been authenticated yet, we won't have any token
        // to charge this with. Ideally, should never reach this stage though.
        //
        if ($subscription->hasBeenAuthenticated() === false)
        {
            throw new LogicException(
                'Should not have reached here. The subscription is not ' .
                'chargeable because it is still in created state.',
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'status'            => $subscription->getStatus(),
                ]);
        }

        $queue  = boolval($options['queue'] ?? true);

        if ($queue === true)
        {
            return (new Charge)->fireCharge($queuePayload);

            //
            // We should move to queue. But, right now we are not, because
            // of issues with figuring out the auth for recurring.
            // Will fix this later and then move to queue.
            //
            // ChargeSubscription::dispatch($queuePayload);
            // return true;
        }
        else
        {
            return (new Charge)->fireCharge($queuePayload);
        }
    }

    public function retryCapture(Entity $subscription, Invoice\Entity $invoice, array $options = [])
    {
        $manual = boolval($options['manual'] ?? false);

        $payments = $invoice->payments;

        $authorizedPayments = $payments->where(Payment\Entity::STATUS, '=', Payment\Status::AUTHORIZED);

        $authorizedPaymentsCount = $authorizedPayments->count();

        if ($authorizedPaymentsCount === 1)
        {
            $authorizedPayment = $authorizedPayments->first();
        }
        else if ($authorizedPaymentsCount === 0)
        {
            return false;
        }
        else
        {
            //
            // If a capture has failed, there would be always only one authorized payment.
            // There's no concept of late authorization payments when authorization is being
            // done in S2S flow.
            // The only late auth that CAN happen is when the customer does a retry via 2FA.
            // But, in this, it's not associated to any invoice.
            //

            throw new LogicException(
                'There should not have been more than one authorized payment for the invoice',
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'invoice_id'        => $invoice->getId(),
                ]);
        }

        $capturePayload = [
            Payment\Entity::AMOUNT   => $authorizedPayment->getAmount(),
            Payment\Entity::CURRENCY => $authorizedPayment->getCurrency()
        ];

        $processor = (new Payment\Processor\Processor($subscription->merchant));

        $capturedPayment = null;

        try
        {
            if ($manual === false)
            {
                $subscription->incrementAuthAttempts();
            }

            // Might want to move this to a queue later.
            $capturedPayment = $processor->capture($authorizedPayment, $capturePayload);

            $captured = true;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            if ($manual === false)
            {
                (new Charge)->handleAuthorizationOrCaptureFailure($subscription, $invoice, $authorizedPayment, true);
            }

            $captured = false;
        }

        //
        // This is required here because we don't run handleCaptureSuccess in the normal capture flow.
        // TODO: We should add this in the normal capture flow after checking for some conditions
        // so that if a merchant manually captures an authorized payment from the dashboard, everything
        // would still work fine. This retry route allows the merchant to retry an invoice. But there's
        // nothing stopping him from trying capture the actual payment itself. We should update the subscription
        // like in the retry flow itself!
        //

        if ($captured === true)
        {
            (new Charge)->handleCaptureSuccess($subscription, $capturedPayment, $invoice);
        }

        $notifyOptions = [
            Event::PAYMENT => $capturedPayment,
        ];

        $this->triggerSubscriptionNotification($subscription, Event::CHARGED, $notifyOptions);

        return $captured;
    }

    /**
     * This function would handle emails for all failures.
     * The status would become either pending, halted or completed.
     * It calls the different events based on the statuses.
     *
     * @param Entity $subscription
     *
     * @throws LogicException
     */
    public function triggerSubscriptionFailureNotification(Entity $subscription)
    {
        if (in_array($subscription->getStatus(), Status::$failingStatuses, true) === false)
        {
            throw new LogicException(
                'Should have never reached here. Should only be called for a subscription fail status',
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_INVOICE_BAD_STATUS,
                [
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                ]);
        }

        $notifyOptions = [];

        //
        // Completed notification has options, it can be
        // triggered by charge success as well as failure.
        //
        if ($subscription->isCompleted() === true)
        {
            $notifyOptions[Event::CHARGE_SUCCESS] = false;
        }

        $status = $subscription->getStatus();

        $event = constant(Event::class . '::' . strtoupper($status));

        $this->triggerSubscriptionNotification($subscription, $event, $notifyOptions);
    }

    public function cancel(Entity $subscription, array $input): Entity
    {
        $this->trace->info(
            TraceCode::SUBSCRIPTION_CANCEL_REQUEST,
            [
                'subscription_id'   => $subscription->getId(),
                'input'             => $input,
            ]);

        $validator = $subscription->getValidator();

        $validator->validateSubscriptionCancellable();

        $validator->validateInput(Validator::CANCEL, $input);

        $subscription = $this->mutex->acquireAndRelease(
            $subscription->getId(),
            function () use ($subscription, $input)
            {
                if ((isset($input[Entity::CANCEL_AT_CYCLE_END]) === true) and
                    (boolval($input[Entity::CANCEL_AT_CYCLE_END]) === true))
                {
                    $this->setupCancelAtCycleEnd($subscription);
                }
                else
                {
                    //
                    // If we first received cancel_at_cycle_end and then we received
                    // cancel immediately, then we reset everything that was set as
                    // part of the earlier request. We override the earlier request
                    // with the current request.
                    //
                    if ($subscription->getCancelAtCycleEnd() === true)
                    {
                        $subscription->setCancelAt(null);
                        $subscription->setCancelledAt(null);
                    }

                    $this->cancelImmediately($subscription);
                }

                return $subscription;
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SUBSCRIPTION_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CANCELLED,
            [
                'subscription_id'   => $subscription->getId(),
                'input'             => $input,
            ]);

        return $subscription;
    }

    public function triggerSubscriptionAuthenticatedNotification(
        Payment\Entity $payment,
        Entity $subscription)
    {
        $willBeRefunded = true;

        if (($subscription->hadUpfrontAmount() === true) or
            ($subscription->wasImmediate() === true))
        {
            $willBeRefunded = false;
        }

        $options = [
            Event::AUTO_REFUND => $willBeRefunded,
            Event::IMMEDIATE   => $subscription->wasImmediate(),
            Event::PAYMENT     => $payment,
        ];

        $this->triggerSubscriptionNotification(
            $subscription, Event::AUTHENTICATED, $options);
    }

    public function triggerSubscriptionAlreadyAuthenticatedNotification(
        Entity $subscription,
        string $oldStatus,
        array $options)
    {
        $event = Event::CHARGED;

        //
        // If new status is completed, then that _might_ be the mail we have to send
        //
        if ($subscription->isCompleted() === true)
        {
            //
            // If old status was halted, we would never have reached here. A manual charge on an older
            // invoice can take a subscription from halted to active, but not from halted to completed.
            //
            // The only way there is for a subscription to go from halted to completed
            // state is via the handleNoSubscriptionChargeAtInvoiceCreation flow.
            //
            if ($oldStatus === Status::HALTED)
            {
                throw new LogicException(
                    'Subscription cannot be in halted state here',
                    ErrorCode::BAD_REQUEST_SUBSCRIPTION_INVALID_STATUS,
                    [
                        'subscription_id'       => $subscription->getId(),
                        'subscription_status'   => $subscription->getStatus(),
                        'old_status'            => $oldStatus
                    ]);
            }

            //
            // If old status was pending, actual movement was pending->active->completed
            // In that case sending a completed mail is fine. Same for active.
            //
            // If subscription is already completed, there is not need to send another
            // such notification. Sending a charge email would be more appropriate.
            //
            if ($oldStatus !== Status::COMPLETED)
            {
                $event = Event::COMPLETED;

                //
                // Completed mails can come via a charge failure as well
                //
                $options[Event::CHARGE_SUCCESS] = true;
            }
        }

        $payment = $options[Event::PAYMENT];

        //
        // If the charge is on an older invoice, mention this in the mail
        //
        if ($subscription->isLatestInvoiceForSubscription($payment->invoice) === false)
        {
            $options[Event::PAST_INVOICE] = true;
        }

        //
        // Mention that the subscription has been reactivated.
        //
        if ($oldStatus !== Status::ACTIVE)
        {
            $options[Event::REACTIVATED] = true;
        }

        $this->triggerSubscriptionNotification($subscription, $event, $options);
    }

    public function triggerSubscriptionNotification(
        Entity $subscription,
        string $event,
        array $options = [])
    {
        assertTrue ($this->repo->isTransactionActive() === false);

        $notifier = new Notify($subscription, $options);

        $notifier->trigger($event);
    }

    protected function setupCancelAtCycleEnd(Entity $subscription)
    {
        //
        // TODO: Handle race conditions here.
        // If the subscription is picked up by
        // the cron to cancel it and the status is
        // changed to false here, don't allow it.
        //

        $currentCycleEnd = $subscription->getCurrentEnd();

        //
        // If the subscription is in created or authenticated state
        // and a cancel at cycle end request is sent
        //
        if ($currentCycleEnd === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CYCLE_NOT_RUNNING,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'start_at'          => $subscription->getStartAt(),
                    'charge_at'         => $subscription->getChargeAt(),
                ]);
        }

        //
        // This would ideally never happen since the subscription would
        // be in completed state if this condition has to be true. If it
        // is in completed state, we fail the validation before itself.
        //
        if ($currentCycleEnd === $subscription->getEndAt())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_LAST_CYCLE_CANNOT_CANCEL,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'start_at'          => $subscription->getStartAt(),
                    'current_cycle_end' => $currentCycleEnd,
                ]);
        }

        $currentTime = Carbon::now()->getTimestamp();

        if ($currentCycleEnd < $currentTime)
        {
            throw new LogicException(
                'Current cycle\'s cannot be lesser than the current time!',
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'current_time'      => $currentTime,
                    'current_cycle_end' => $currentCycleEnd,
                    'charge_at'         => $subscription->getChargeAt(),
                ]);
        }

        $oldStatus = $subscription->getStatus();

        $subscription->setCancelAt($currentCycleEnd);

        $subscription->setCancelledAt($currentTime);

        $this->repo->saveOrFail($subscription);

        $options = [
            Event::FUTURE_CANCEL => true,
        ];

        $this->triggerSubscriptionCancelledNotification($subscription, $oldStatus, $options);
    }

    public function cancelImmediately(Entity $subscription)
    {
        $oldStatus = $subscription->getStatus();

        $subscription->setStatus(Status::CANCELLED);

        $this->setFieldsOnCancel($subscription);

        $this->repo->saveOrFail($subscription);

        $this->fireWebhookForStatusUpdate($subscription, Status::CANCELLED);

        $this->triggerSubscriptionCancelledNotification($subscription, $oldStatus);
    }

    protected function triggerSubscriptionCancelledNotification(
        Entity $subscription,
        string $oldStatus,
        array $options = [])
    {
        if (($oldStatus !== Status::HALTED) and
            ($oldStatus !== Status::CREATED))
        {
            // TODO: Add a subscription setting flag to send cancel emails

            $this->triggerSubscriptionNotification($subscription, Event::CANCELLED, $options);
        }
    }

    protected function setFieldsOnCancel(Entity $subscription)
    {
        $subscription->resetAuthAttempts();

        $subscription->setEndedAt($subscription->getCancelledAt());
    }

    protected function getAuthTransactionAmountForNewSubscription(Entity $subscription): int
    {
        if ($subscription->isFutureNotUpfront() === true)
        {
            if ($subscription->plan->item->getCurrency() === Currency::INR)
            {
                return Entity::DEFAULT_AUTH_AMOUNT;
            }

            $currencyDetails = Currency::getDetails();

            return $currencyDetails[$subscription->plan->item->getCurrency()]['min_auth_value'];
        }
        else
        {
            $invoices = $this->repo->invoice->fetchIssuedInvoicesOfSubscription($subscription);

            if ($invoices->count() !== 1)
            {
                throw new LogicException(
                    'Number of invoices found for subscription does not match 1',
                    ErrorCode::SERVER_ERROR_INCORRECT_NUMBER_OF_INVOICES_FOUND,
                    [
                        'count'             => $invoices->count(),
                        'subscription_id'   => $subscription->getId(),
                    ]);
            }

            $authAmount = $invoices->first()->getAmount();
        }

        return $authAmount;
    }

    protected function constructRecurringPayload(
        Entity $subscription,
        Invoice\Entity $invoice,
        array $options): array
    {
        //
        // Ensure that invoice amount is taken always because
        // that would take care of addons and stuff.
        //
        $subscriptionAmount = $invoice->getAmount();

        $customer = $subscription->customer;

        $tokenId = null;

        if ($subscription->token !== null)
        {
            $tokenId  = $subscription->token->getPublicId();
        }

        $order    = $invoice->order;

        $recurringPayload = [
            Payment\Entity::AMOUNT          => $subscriptionAmount,
            Payment\Entity::CURRENCY        => $invoice->getCurrency(),
            Payment\Entity::RECURRING       => '1',
            Payment\Entity::SUBSCRIPTION_ID => $subscription->getPublicId(),
            Payment\Entity::TOKEN           => $tokenId,
            // Payment\Entity::CUSTOMER_ID     => $customer->getPublicId(),
            Payment\Entity::ORDER_ID        => $order->getPublicId(),
            // TODO: These fields should not be required to be sent.
            Payment\Entity::EMAIL           => $customer->getEmail() ?: Payment\Entity::DUMMY_EMAIL,
            Payment\Entity::CONTACT         => $customer->getContact() ?: Payment\Entity::DUMMY_PHONE,
            Payment\Entity::DESCRIPTION     => 'Recurring Payment via Subscription',
        ];

        $this->addTestChargeOptions($recurringPayload, $options);

        return $recurringPayload;
    }

    protected function addTestChargeOptions(array & $recurringPayload, array $options)
    {
        if (($this->mode === Constants\Mode::TEST) and
            (isset($options['success']) === true))
        {
            $recurringPayload['test_success'] = boolval($options['success']);

            if ($recurringPayload['test_success'] === false)
            {
                $recurringPayload['description'] = 'Failed Recurring Payment via Subscription';
            }
        }
    }
}
