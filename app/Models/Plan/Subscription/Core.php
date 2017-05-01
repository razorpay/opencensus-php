<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Plan;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;
use RZP\Models\AddOn;
use RZP\Models\Schedule;
use RZP\Models\Schedule\Task;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input, Plan\Entity $plan, Customer\Entity $customer) : Entity
    {
        $subscription = (new Entity)->build($input);

        //
        // Transaction on live and test is required because
        // schedule is created in both live and test.
        //
        $this->repo->transactionOnLiveAndTest(
            function() use ($subscription, $plan, $customer, $input)
            {
                // This is being done for the `run` association.
                $subscription->generateId();

                //
                // This should be called before creating task since it requires
                // merchant to associated with the subscription first.
                //
                $this->associateEntitiesToSubscription($subscription, $plan, $customer);

                //
                // This should be called before filling end_at and total_count,
                // since they require the schedule to be created first.
                //
                $this->createScheduleAndTask($subscription, $plan);

                $this->fillEndAtAndTotalCount($subscription, $plan);

                $this->repo->saveOrFail($subscription);

                //
                // This needs to be done after saving the subscription
                // because invoice/add_on is created and saved in the
                // following step, with the subscription_id.
                //

                $this->createAddOnsIfApplicable($subscription, $input);

                $this->createInvoiceIfApplicable($subscription);
            });

        return $subscription;
    }

    public function retry(Entity $subscription)
    {
        $invoice = $this->repo->invoice->fetchIssuedAndNotOnHoldInvoiceForSubscription($subscription);

        $this->charge($subscription, $invoice);
    }

    public function fillScheduleDetailsForNewSubscription(Entity $subscription)
    {
        //
        // We don't have to update the next_run_at here
        // because `handleCaptureSuccess` will take care of that.
        // When the task was first created, the start_at of subscription
        // would have been null, which means that the next_run_at
        // would have got set to the midnight of subscription creation
        // date (default).
        // It will not get picked up by the cron also because of the
        // subscription status being in created state.
        // Now, since we set `anchor` here, the next_run_at of the task
        // will automatically get set to the correct next_run
        // according to the anchor.
        //

        if ($subscription->isAuthenticated() === false)
        {
            throw new LogicException(
                'Subscription is not in authenticated state. This function should not have been called',
                null,
                [
                    'subscription_id' => $subscription->getId(),
                    'status' => $subscription->getStatus(),
                ]);
        }

        $schedule = $subscription->schedule;

        $anchor = $this->getAnchorForSchedule($subscription);

        $schedule->setAnchor($anchor);

        $this->repo->saveOrFail($schedule);
    }

    public function fillEndAtAndTotalCount(Entity $subscription, Plan\Entity $plan)
    {
        $startAt = $subscription->getStartAt();

        //
        // We get the start_at at the time of first charge.
        // We fill end_at and total_count at that time.
        //
        if ($startAt === null)
        {
            return;
        }

        if ($subscription->getTotalCount() === null)
        {
            $this->calculateAndSetTotalCount($subscription, $plan);
        }
        else if ($subscription->getEndAt() === null)
        {
            $this->calculateAndSetEndAt($subscription);

            $subscription->getValidator()->validateEndAtAfterGenerating();
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'plan_id'           => $plan->getId(),
                    'total_count'       => $subscription->getTotalCount(),
                    'end_at'            => $subscription->getEndAt(),
                ]);
        }
    }

    public function createInvoiceAndCharge(Entity $subscription)
    {
        $invoice = $this->createInvoiceBeforeCharge($subscription);

        //
        // We should not charge any invoice which is in on_hold status,
        // since, the subscription would also be in on_hold status here.
        // We do not charge on_hold subscriptions, we only create an invoice.
        //
        if ($invoice->getSubStatus() === Invoice\Status::ON_HOLD)
        {
            $this->trace->info(
                TraceCode::SUBSCRIPTION_INVOICE_ON_HOLD,
                [
                    'invoice_id'        => $invoice->getId(),
                    'subscription_id'   => $subscription->getId(),
                ]);

            return;
        }

        $this->charge($subscription, $invoice);
    }

    public function expireSubscription(Entity $subscription)
    {
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

    /**
     * Subscription need not be updated if it's in created or activated state.
     * That flow would be taken care by the normal subscription capture flow.
     *
     * Only if it's in on_hold state with capture_failure as error, we need to
     * explicitly update the subscription. This is because, this capture would
     * have been an explicit call and not via normal subscription flow.
     *
     * @param Entity $subscription
     * @param Payment\Entity $capturedPayment
     * @return bool
     */
    public function shouldUpdateSubscriptionOnCapture(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $status = $subscription->getStatus();
        $errorStatus = $subscription->getErrorStatus();

        if ($status !== Status::ON_HOLD)
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

    public function getFormattedSubscriptionData(Merchant\Entity $merchant, string $subscriptionId)
    {
        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $merchant);

        $authAmount = $this->getAuthTransactionAmount($subscription);

        return [
            'auth_amount' => $authAmount,
        ];
    }

    /**
     * upfront_amount | start_at | charge_amount
     * ----------------------------------------------------------
     * yes            | no       | upfront_amount + plan_amount
     * no             | yes      | default_auth_amount (5rs)
     * yes            | yes      | upfront_amount
     * no             | no       | plan_amount
     *
     * @param Entity $subscription
     *
     * @return int
     * @throws LogicException
     */
    public function getAuthTransactionAmount(Entity $subscription)
    {
        $invoices = $this->repo->invoice->fetchIssuedInvoicesOfSubscription($subscription);

        $invoicesCount = $invoices->count();

        if ($invoicesCount === 0)
        {
            $authAmount = Entity::DEFAULT_AUTH_AMOUNT;
        }
        else if ($invoicesCount === 1)
        {
            $authAmount = $invoices->first()->getAmount();
        }
        else
        {
            throw new LogicException(
                'Number of invoices found for subscription does not match 1',
                ErrorCode::SERVER_ERROR_INVOICE_COUNT_MISMATCH,
                [
                    'count'             => $invoicesCount,
                    'subscription_id'   => $subscription->getId(),
                ]);
        }

        return $authAmount;
    }

    public function fireWebhookForStatusUpdate(Entity $subscription, string $status)
    {
        if (array_key_exists($status, Status::$webhookStatuses[$status]) === false)
        {
            return;
        }

        $event = Status::$webhookStatuses[$status];

        $this->app['events']->fire('api.' . $event, array($subscription));
    }

    public function charge(Entity $subscription, Invoice\Entity $invoice, $manual = false)
    {
        $this->mutex->acquireAndRelease(
            $subscription->getId(),
            function() use($subscription, $invoice, $manual)
            {
                $recurringPayload = $this->constructRecurringPayload($subscription, $invoice);

                $queuePayload = [
                    'recurring_payload' => $recurringPayload,
                    'subscription_id'   => $subscription->getId(),
                    'invoice_id'        => $invoice->getId(),
                    // This would almost always be rzp_{mode},since it will be
                    // run via cron. We actually need the mode here. But basicauth
                    // functions mostly work on the key. Hence, sending the key
                    // across rather than the mode.
                    'key_id'            => $this->app['basicauth']->getPublicKey(),
                    'manual'            => $manual,
                ];

                $this->trace->info(
                    TraceCode::SUBSCRIPTION_CHARGE_QUEUE_PAYLOAD_REQUEST,
                    $queuePayload);

                // If the status is in created state, this means that the token has not
                // been associated with it yet. An authorized payment for this subscription
                // has not been done.
                if ($subscription->getStatus() === Status::CREATED)
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

                $this->app['queue']->push(Charge::class . '@fireCharge', $queuePayload);
            }
        );
    }

    protected function createInvoiceBeforeCharge(Entity $subscription)
    {
        return $this->repo->transaction(
            function() use ($subscription)
            {
                //
                // If first charge, we set the status to active.
                // If not, the status would already be active or
                // would be reset by some other flow (auth/capture).
                //
                if ($subscription->getPaidCount() === 0)
                {
                    $this->activateSubscription($subscription);
                }

                $addOns = $this->repo->add_on->getUnusedAddOnsForSubscription($subscription);

                $invoice = $this->createInvoiceForSubscription($subscription, $addOns);

                return $invoice;
            });

    }

    protected function createInvoiceForSubscription(Entity $subscription, $addOns, bool $first = false)
    {
        $merchant = $subscription->merchant;

        $invoiceInput = $this->getInvoiceInput($subscription, $addOns, $first);

        $invoice = (new Invoice\Core)->create($invoiceInput, $merchant, $subscription);

        $this->associateInvoiceToAddOns($invoice, $addOns);

        $this->trace->info(
            TraceCode::SUBSCRIPTION_INVOICE_CREATED,
            [
                'invoice_id'      => $invoice->getId(),
                'subscription_id' => $subscription->getId(),
                'invoice_details' => $invoice->toArray(),
            ]);

        return $invoice;
    }

    protected function associateInvoiceToAddOns(Invoice\Entity $invoice, $addOns)
    {
        foreach ($addOns as $addOn)
        {
            $addOn->invoice()->associate($invoice);
            $this->repo->saveOrFail($addOn);
        }
    }

    protected function activateSubscription(Entity $subscription)
    {
        if ($subscription->getStatus() !== Status::AUTHENTICATED)
        {
            throw new LogicException(
                'The status should have been authenticated since the subscription has not been paid even once.',
                null,
                [
                    'status'          => $subscription->getStatus(),
                    'subscription_id' => $subscription->getId()
                ]);
        }

        // TODO: Fire a webhook in sync for activate subscription -- otherwise charge webhook might go before this.

        $subscription->setStatus(Status::ACTIVE);
        $this->repo->saveOrFail($subscription);
    }

    protected function createAddOnsIfApplicable(Entity $subscription, array $input)
    {
        if (empty($input[Entity::ADD_ONS]) === true)
        {
            return;
        }

        $addOnsInput = $input[Entity::ADD_ONS];

        $addOnCore = (new AddOn\Core);

        foreach ($addOnsInput as $addOnInput)
        {
            $addOnCore->create($addOnInput, $subscription);
        }
    }

    /**
     * We create an invoice only if the auth transaction includes the
     * first charge also. This invoice will be used when the payment
     * for the auth txn (first charge) is made.
     *
     * If the auth txn also includes the upfront_amount, the invoice
     * will be made for plan_amount + upfront_amount.
     *
     * @param Entity $subscription
     */
    protected function createInvoiceIfApplicable(Entity $subscription)
    {
        $addOns = $this->repo->add_on->getUnusedAddOnsForSubscription($subscription);

        if (($addOns->count() === 0) and
            ($subscription->getStartAt() !== null))
        {
            return;
        }

        $this->createInvoiceForSubscription($subscription, $addOns, true);
    }

    protected function getInvoiceInput(Entity $subscription, $addOns, bool $first)
    {
        $plan = $subscription->plan;
        $customer = $subscription->customer;

        $lineItems = $this->getLineItemsForInvoiceInput($subscription, $addOns, $first);

        $invoiceInput = [
            Invoice\Entity::CUSTOMER_ID     => $customer->getPublicId(),
            Invoice\Entity::LINE_ITEMS      => $lineItems,
            Invoice\Entity::CURRENCY        => $plan->getCurrency(),
            Invoice\Entity::SMS_NOTIFY      => '0',
            Invoice\Entity::EMAIL_NOTIFY    => '0',
        ];

        return $invoiceInput;
    }

    protected function getLineItemsForInvoiceInput(Entity $subscription, $addOns, bool $first)
    {
        $plan = $subscription->plan;

        $lineItems = [];

        //
        // If it's not the first charge, we always have to create an invoice
        // line item with the plan amount and all. The main line item, basically.
        // If it's the first charge, we should ONLY create IF the auth txn also
        // includes the first charge.
        //
        if (($first === false) or
            (($first === true) and ($subscription->getStartAt() === null)))
        {
            // TODO: The amount may differ in the case of pro-rate.

            $mainLineItem = [
                LineItem\Entity::NAME     => $plan->getName(),
                LineItem\Entity::AMOUNT   => $plan->getAmount(),
                LineItem\Entity::CURRENCY => $plan->getCurrency(),
                LineItem\Entity::QUANTITY => $subscription->getQuantity(),
            ];

            $lineItems[] = $mainLineItem;
        }

        foreach ($addOns as $addOn)
        {
            $addOnLineItem = [
                LineItem\Entity::ITEM_ID => $addOn->item->getPublicId(),
                LineItem\Entity::ADD_ON_ID => $addOn->getPublicId(),
            ];

            $lineItems[] = $addOnLineItem;
        }

        return $lineItems;
    }

    protected function createScheduleAndTask(Entity $subscription, Plan\Entity $plan)
    {
        $schedule = $this->createSchedule($subscription, $plan);

        $subscription->schedule()->associate($schedule);

        $this->createTask($subscription);
    }

    protected function createSchedule(Entity $subscription, Plan\Entity $plan)
    {
        $scheduleInput = [
            Schedule\Entity::NAME       => $plan->getName(),
            Schedule\Entity::INTERVAL   => $plan->getInterval(),
            Schedule\Entity::PERIOD     => $plan->getPeriod(),
        ];

        if ($subscription->getStartAt() !== null)
        {
            $scheduleInput[Schedule\Entity::ANCHOR] = $this->getAnchorForSchedule($subscription);
        }

        $schedule = (new Schedule\Core)->createSchedule($scheduleInput);

        return $schedule;
    }

    protected function createTask(Entity $subscription)
    {
        $schedule = $subscription->schedule;

        $taskInput = [
            Task\Entity::METHOD         => null,
            Task\Entity::TYPE           => Task\Type::SUBSCRIPTION,
            Task\Entity::SCHEDULE_ID    => $schedule->getId(),
            // TODO: During first charge auth txn, update the task's next run_at.
            Task\Entity::NEXT_RUN_AT    => $subscription->getStartAt(),
        ];

        (new Task\Core)->createOrUpdate($subscription->merchant, $subscription, $taskInput);
    }

    protected function getAnchorForSchedule(Entity $subscription)
    {
        // TODO: Handle setting anchor for weekly and monthly-week

        if ($subscription->getStartAt() !== null)
        {
            $startAt = Carbon::createFromTimestamp($subscription->getStartAt(), 'Asia/Kolkata');

            return $startAt->day;
        }

        return null;
    }

    protected function constructRecurringPayload(Entity $subscription, Invoice\Entity $invoice)
    {
        //
        // Ensure that invoice amount is taken always because
        // that would take care of add_ons and stuff.
        //
        $subscriptionAmount = $invoice->getAmount();

        $customer = $subscription->customer;
        $tokenId = $subscription->token->getPublicId();
        $order = $invoice->order;

        $recurringPayload = [
            Payment\Entity::AMOUNT          => $subscriptionAmount,
            Payment\Entity::CURRENCY        => Payment\Entity::DEFAULT_CURRENCY,
            Payment\Entity::RECURRING       => '1',
            Payment\Entity::SUBSCRIPTION_ID => $subscription->getPublicId(),
            Payment\Entity::TOKEN           => $tokenId,
            Payment\Entity::CUSTOMER_ID     => $customer->getPublicId(),
            Payment\Entity::ORDER_ID        => $order->getPublicId(),
            Payment\Entity::EMAIL           => $customer->getEmail(),
            Payment\Entity::CONTACT         => $customer->getContact(),
            Payment\Entity::DESCRIPTION     => 'Recurring Payment via Subscription',
        ];

        return $recurringPayload;
    }

    protected function associateEntitiesToSubscription(
        Entity $subscription,
        Plan\Entity $plan,
        Customer\Entity $customer)
    {
        $merchant = $customer->merchant;

        $subscription->merchant()->associate($merchant);
        $subscription->plan()->associate($plan);
        $subscription->customer()->associate($customer);
    }

    protected function calculateAndSetEndAt(Entity $subscription)
    {
        $endAt = Plan\Cycle::getEndTimeForGivenTotalCount($subscription);

        $subscription->setEndAt($endAt);
    }

    protected function calculateAndSetTotalCount(Entity $subscription)
    {
        $totalCount = Plan\Cycle::getTotalCountForGivenInterval($subscription);

        $subscription->setTotalCount($totalCount);
    }
}
