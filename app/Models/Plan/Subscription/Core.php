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
use RZP\Models\Schedule\Run;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input, Plan\Entity $plan, Customer\Entity $customer)
    {
        $subscription = (new Entity)->build($input);

        $this->repo->transaction(
            function() use ($subscription, $plan, $customer, $input)
            {
                // This is being done for the `run` association.
                $subscription->generateId();

                $this->fillEndAtAndTotalCount($subscription, $plan);

                $this->associateEntitiesToSubscription($subscription, $plan, $customer);

                $this->createRun($subscription, $plan, $input);

                $this->repo->saveOrFail($subscription);

                //
                // This needs to be done after saving the subscription
                // because invoice is created and saved in the following step,
                // with the subscription_id.
                //
                $this->createInvoiceIfApplicable($subscription);
            });

        return $subscription;
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
            $this->calculateAndSetEndAt($subscription, $plan);

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

    public function createInvoiceBeforeCharge(Entity $subscription)
    {
        $this->repo->transaction(
            function() use ($subscription)
            {
                $merchant = $subscription->merchant;

                //
                // If first charge, we set the status to active.
                // If not, the status would already be active or
                // would be reset by some other flow (auth/capture).
                //
                if ($subscription->getPaidCount() === 0)
                {
                    $this->activateSubscription($subscription);
                }

                $invoiceInput = $this->getInvoiceInput($subscription);

                $invoice = (new Invoice\Core)->create($invoiceInput, $merchant, $subscription);

                $this->trace->info(
                    TraceCode::SUBSCRIPTION_INVOICE_CREATED,
                    [
                        'invoice_id' => $invoice->getId(),
                        'subscription_id' => $subscription->getId(),
                        'invoice_details' => $invoice->toArray(),
                    ]);
            });
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

    protected function activateSubscription(Entity $subscription)
    {
        if ($subscription->getStatus() !== Status::AUTHENTICATED)
        {
            throw new LogicException(
                'The status should have been authenticated since the subscription has not been paid even once.',
                null,
                [
                    'status' => $subscription->getStatus(),
                    'subscription_id' => $subscription->getId()
                ]);
        }

        // TODO: Fire a webhook in sync for activate subscription -- otherwise charge webhook might go before this.

        $subscription->setStatus(Status::ACTIVE);
        $this->repo->saveOrFail($subscription);
    }

    /**
     * We create an invoice only if the auth transaction includes the
     * first charge also. This invoice will be used when the payment
     * for the auth txn (first charge) is made.
     *
     * If the auth txn also includes the upfront_amount, the invoice
     * will be made for plan_amount + upfront_amount.
     *
     * But, if the auth txn only includes the upfront_amount,
     * we do not create any invoice at all.
     *
     * @param Entity $subscription
     */
    protected function createInvoiceIfApplicable(Entity $subscription)
    {
        if ($subscription->getStartAt() !== null)
        {
            return;
        }

        $merchant = $subscription->merchant;

        $invoiceInput = $this->getInvoiceInput($subscription);

        $lineItemAmount = & $invoiceInput[Invoice\Entity::LINE_ITEMS][0][LineItem\Entity::AMOUNT];

        // TODO: Create a separate line_item for upfront amount
        // instead of adding to the line_item itself.
        if ($subscription->getUpfrontAmount() !== null)
        {
            $lineItemAmount += $subscription->getUpfrontAmount();
        }

        $invoice = (new Invoice\Core)->create($invoiceInput, $merchant, $subscription);

        $this->trace->info(
            TraceCode::SUBSCRIPTION_INVOICE_CREATED,
            [
                'invoice_id' => $invoice->getId(),
                'subscription_id' => $subscription->getId(),
                'invoice_details' => $invoice->toArray(),
            ]);
    }

    protected function getInvoiceInput(Entity $subscription)
    {
        $plan = $subscription->plan;
        $customer = $subscription->customer;

        // TODO: The amount here may differ in cases of prorate.
        $lineItems = [
            [
                LineItem\Entity::NAME => $plan->getName(),
                LineItem\Entity::AMOUNT => $plan->getAmount()
            ]
        ];

        $invoiceInput = [
            Invoice\Entity::CUSTOMER_ID => $customer->getPublicId(),
            Invoice\Entity::LINE_ITEMS => $lineItems,
            Invoice\Entity::CURRENCY => $plan->getCurrency(),
            Invoice\Entity::SMS_NOTIFY => '0',
            Invoice\Entity::EMAIL_NOTIFY => '0',
        ];

        return $invoiceInput;
    }

    public function charge(Entity $subscription, Invoice\Entity $invoice)
    {
        $this->mutex->acquireAndRelease(
            $subscription->getId(),
            function() use($subscription, $invoice)
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
                ];

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

    public function retry(Entity $subscription)
    {
        //$this->charge($subscription, $invoice);
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
            $this->trace->error(
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
     */
    public function getAuthTransactionAmount(Entity $subscription)
    {
        $plan = $subscription->plan;

        $upfrontAmount = $subscription->getUpfrontAmount();
        $planAmount = $plan->getAmount();
        $startAt = $subscription->getStartAt();
        $defaultAuthAmount = Entity::DEFAULT_AUTH_AMOUNT;

        if (empty($upfrontAmount) === true)
        {
            if (empty($startAt) === true)
            {
                $authAmount = $planAmount;
            }
            else
            {
                $authAmount = $defaultAuthAmount;
            }
        }
        else
        {
            if (empty($startAt) === true)
            {
                $authAmount = $upfrontAmount + $planAmount;
            }
            else
            {
                $authAmount = $upfrontAmount;
            }
        }

        return $authAmount;
    }

    protected function createRun(Entity $subscription, Plan\Entity $plan, array $input)
    {
        $schedule = $plan->schedule;

        // TODO: Remove this once we start using schedules properly.
        if ($schedule === null)
        {
            return null;
        }

        // TODO: Will need fixes later. This may also be null in some cases.
        $runInput = [
            Run\Entity::NEXT_RUN_AT => $subscription->getStartAt(),
        ];

        $run = (new Run\Core)->createRun($schedule, $subscription, $runInput);

        return $run;
    }

    protected function constructRecurringPayload(Entity $subscription, Invoice\Entity $invoice)
    {
        $subscriptionAmount = $subscription->getChargeableAmount();
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

    protected function calculateAndSetEndAt(Entity $subscription, Plan\Entity $plan)
    {
        $startAt = $subscription->getStartAt();
        $totalCount = $subscription->getTotalCount();

        $endAt = Plan\Cycle::getEndTimeForGivenTotalCount($plan, $startAt, $totalCount);

        $subscription->setEndAt($endAt);
    }

    protected function calculateAndSetTotalCount(Entity $subscription, Plan\Entity $plan)
    {
        $startAt = $subscription->getStartAt();
        $endAt = $subscription->getEndAt();

        $totalCount = Plan\Cycle::getTotalCountForGivenInterval($plan, $startAt, $endAt);

        $subscription->setTotalCount($totalCount);
    }
}
