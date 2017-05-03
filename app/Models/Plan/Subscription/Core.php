<?php

namespace RZP\Models\Plan\Subscription;

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
        return (new Creator)->create($input, $plan, $customer);
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

        $anchor = $subscription->getAnchorForSchedule();

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
            $this->calculateAndSetTotalCount($subscription);
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
     * add_on_amount  | start_at | charge_amount
     * ----------------------------------------------------------
     * yes            | no       | add_on_amount + plan_amount
     * no             | yes      | default_auth_amount (5rs)
     * yes            | yes      | add_on_amount
     * no             | no       | plan_amount
     *
     * The above amount is taken care of when we create an invoice
     * and hence not doing those checks here.
     *
     * @param Entity $subscription
     *
     * @return int
     * @throws LogicException
     */
    public function getAuthTransactionAmount(Entity $subscription) : int
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
            Payment\Entity::CURRENCY        => $invoice->getCurrency(),
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
