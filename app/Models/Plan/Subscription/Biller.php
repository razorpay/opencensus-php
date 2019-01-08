<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\LineItem;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Library;

/**
 * Takes care of billing life-cycle of a subscription which can include
 * creating invoice and creating a corresponding payment.
 * First time invoice creation for a subscription when auth is being done
 * is a special case.
 */
class Biller extends Base\Core
{
    //
    // Charging covers invoice creation, payment auth and capture, alongside
    // some other third party requests like queueing jobs for mails and webhooks.
    //
    // 10 minutes is probably overkill (it's the typical duration of the entire cron on API),
    // but there's no reason this lock should ever be given up to a parallel process at all
    // (since a parallel process shouldn't exist), so this is effectively a proxy for a lock
    // that outlives all parallel processes.
    // Since the processes won't be retrying anyway, 10 minutes is enough.
    //
    const CHARGE_LOCK_TTL = 600;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Creates invoice, conditionally charges. Charge is
     * not done for invoices of halted subscriptions.
     *
     * @param  Entity $subscription
     * @param  array  $options      List of options for use by merchant, that alter
     *                              the flow of charge.
     *                              - manual: Leaves auth_attempts, pending status unchanged
     *                              - queue: Charges in queue, rather than in sync
     *                              - success: For test charge, allows testing failures
     *
     * @return bool
     */
    public function createInvoiceAndCharge(Entity $subscription, array $options = [])
    {
        // Subscription may have been queued and charged by a different process
        if (($subscription->isChargeable() === false) and
            ($options['queue'] !== false))
        {
            $this->trace->info(
                TraceCode::SUBSCRIPTION_ALREADY_CHARGED,
                [
                    'subscription_id' => $subscription->getId(),
                ]);

            return;
        }

        $resource = $subscription->getId();

        if ($this->mutex->acquire($resource, self::CHARGE_LOCK_TTL) === false)
        {
            // We don't throw an exception here, since we don't want the charge
            // job to be retried. Instead we're simply returning to end the job.

            $this->trace->info(
                TraceCode::SUBSCRIPTION_CHARGE_IN_PROGRESS,
                [
                    'subscription_id' => $subscription->getId(),
                ]);

            return;
        }

        $data = $this->createInvoiceBeforeCharge($subscription);

        $core = (new Core);

        if ($data['activated'] === true)
        {
            //
            // This might need to be changed, to fire webhook
            // in sync. Otherwise charge webhook might go before
            // this since our queue doesn't maintain order.
            //
            $core->fireWebhookForStatusUpdate($subscription, Status::ACTIVE);
        }

        $invoice = $data['invoice'];

        if ($this->shouldCharge($subscription, $invoice) === true)
        {
            $core->charge($subscription, $invoice, $options);
        }
        else
        {
            //
            // Some attributes of the subscription still need
            // to be updated, so that the flow continues as it
            // is even if the subscription is in halted state.
            //
            $this->handleNoSubscriptionChargeAtInvoiceCreation($subscription, $invoice);
        }

        $this->mutex->release($resource);
    }

    public function createInvoiceForSubscription(
        Entity $subscription,
        Base\PublicCollection $addons,
        bool $first = false): Invoice\Entity
    {
        //
        // This is in a transaction even though the calling functions
        // are already in a transaction, because it's a public function
        // and can be used independently.
        // If a new calling function does not implement this in a transaction,
        // it might be an issue and hence putting this here.
        //

        return $this->repo->transaction(
            function() use($subscription, $addons, $first)
            {
                $merchant = $subscription->merchant;

                $invoiceInput = $this->getInvoiceInput($subscription, $addons, $first);

                $invoice = (new Invoice\Core)->create($invoiceInput, $merchant, $subscription);

                $this->associateInvoiceToAddons($invoice, $addons);

                $this->trace->info(
                    TraceCode::SUBSCRIPTION_INVOICE_CREATED,
                    [
                        'invoice_id'      => $invoice->getId(),
                        'subscription_id' => $subscription->getId(),
                        'invoice_details' => $invoice->toArray(),
                    ]);

                return $invoice;
            });
    }

    /**
     * Sets subscription current period, and then uses that to set invoice
     * billing period. We can do this before the charge, as current period
     * is set to be updated irrespective of the result of the charge attempt.
     *
     * @param  Entity         $subscription Subscription to be updated
     * @param  Invoice\Entity $invoice      Newly created invoice
     */
    public function updateSubscriptionAndInvoiceBillingPeriod(Entity $subscription, Invoice\Entity $invoice)
    {
        $billingPeriod = $this->getBillingPeriod($subscription);

        $subscription->setCurrentPeriod($billingPeriod);

        $invoice->setBillingPeriod($billingPeriod);
    }

    /**
     * We should not charge any invoice which is in halted status,
     * since, the subscription would also be in halted status here.
     * We do not charge halted subscriptions, we only create an invoice.
     *
     * @param Entity         $subscription
     * @param Invoice\Entity $invoice
     *
     * @return bool
     */
    protected function shouldCharge(Entity $subscription, Invoice\Entity $invoice)
    {
        if ($invoice->getSubscriptionStatus() !== Invoice\Status::HALTED)
        {
            return true;
        }

        $this->trace->info(
            TraceCode::SUBSCRIPTION_INVOICE_HALTED,
            [
                'invoice_id'            => $invoice->getId(),
                'subscription_id'       => $subscription->getId(),
                'subscription_status'   => $subscription->getStatus(),
            ]);

        return false;
    }

    protected function getBillingPeriod(Entity $subscription)
    {
        $planChargeInvoiceCount = $subscription->getPlanChargeInvoicesCount();

        $schedule = $subscription->schedule;

        $nextRun = $subscription->getStartAt();
        $nextRun = Carbon::createFromTimestamp($nextRun, Timezone::IST);

        $start = $nextRun->copy();

        // If there's just one invoice, this is first charge period.
        if ($planChargeInvoiceCount > 1)
        {
            //
            // We are subtracting one because the
            // invoice for the current charge has
            // already been created and associated
            //
            foreach (range(1, $planChargeInvoiceCount - 1) as $i)
            {
                $nextRun = Library::computeFutureRun($schedule, $start, $minTime = null, $ignoreBankHolidays = true);

                $start = $nextRun->copy();
            }
        }

        // Cannot pass start here, as computeFutureRun will modify the value
        $end = Library::computeFutureRun($schedule, $nextRun, $minTime = null, $ignoreBankHolidays = true);

        $billingPeriod = [
            'start' => $start->timestamp,
            'end'   => $end->timestamp,
        ];

        return $billingPeriod;
    }

    protected function createInvoiceBeforeCharge(Entity $subscription): array
    {
        return $this->repo->transaction(
            function() use ($subscription)
            {
                $activated = false;

                $addons = $this->repo->addon->getUnusedAddonsForSubscription($subscription);

                $invoice = $this->createInvoiceForSubscription($subscription, $addons);

                //
                // Update current period and billing period before the charge itself. This
                // saves us the hassle of having to update them separately in retry flows.
                //
                $this->updateSubscriptionAndInvoiceBillingPeriod($subscription, $invoice);

                //
                // If first charge, we set the status to active.
                // If not, the status would already be active or
                // would be reset by some other flow (auth/capture).
                //
                // If subscription is halted, we need to create an invoice
                // anyway, but not mark it as activated while doing so.
                //
                // Eg. Subscription is in authenticated state, first charge
                // fails, so does second and third. Subscription moves to
                // halted. One month later, the halted subscription is to
                // be picked up by the charge cron to create an invoice. But
                // we don't want to activate it, even though paid_count is 0.
                //
                if (($subscription->getPaidCount() === 0) and
                    ($subscription->isHalted() === false))
                {
                    $subscription->setStatus(Status::ACTIVE);

                    $activated = true;
                }

                //
                // Subscription billing period updated
                // Subscription status updated
                // Invoice billing period updated
                //
                $this->repo->saveOrFail($invoice);
                $this->repo->saveOrFail($subscription);

                return ['invoice' => $invoice, 'activated' => $activated];
            });
    }

    /**
     * @param Entity         $subscription
     * @param Invoice\Entity $invoice
     */
    protected function handleNoSubscriptionChargeAtInvoiceCreation(Entity $subscription, Invoice\Entity $invoice)
    {
        $charge = (new Charge);

        $task = $subscription->task;

        $task->updateForSubscription($subscription, $this->mode);

        $charge->setEndedAtIfApplicable($subscription);

        //
        // We do not trigger a subscription notification here. Once a subscription is in halted state,
        // the continued generation of invoices (that remain in unattempted, issued state) is just us
        // doing our duty and keeping the subscription going. The merchant may in fact have stopped
        // delivering services long ago, unbeknownst to us. For this reason, we neither inform the
        // customer that an invoice has been created, nor that the subscription is completed.
        //

        $this->repo->transaction(
            function() use ($subscription, $invoice)
            {
                $this->repo->saveOrFail($subscription);

                $this->repo->saveOrFail($subscription->task);

                $this->repo->saveOrFail($invoice);
            });

        if ($subscription->isCompleted() === true)
        {
            (new Core)->fireWebhookForStatusUpdate($subscription, Status::COMPLETED);
        }
    }

    protected function associateInvoiceToAddons(Invoice\Entity $invoice, Base\PublicCollection $addons)
    {
        foreach ($addons as $addon)
        {
            $addon->invoice()->associate($invoice);
            $this->repo->saveOrFail($addon);
        }
    }

    protected function getInvoiceInput(Entity $subscription, Base\PublicCollection $addons, bool $first): array
    {
        $plan = $subscription->plan;
        $customer = $subscription->customer;

        $lineItems = $this->getLineItemsForInvoiceInput($subscription, $addons, $first);

        $invoiceInput = [
            Invoice\Entity::LINE_ITEMS      => $lineItems,
            Invoice\Entity::CURRENCY        => $plan->item->getCurrency(),
            Invoice\Entity::SMS_NOTIFY      => '0',
            Invoice\Entity::EMAIL_NOTIFY    => '0',
        ];

        if ($customer !== null)
        {
            $invoiceInput[Invoice\Entity::CUSTOMER_ID] = $customer->getPublicId();
        }

        return $invoiceInput;
    }

    protected function getLineItemsForInvoiceInput(
        Entity $subscription,
        Base\PublicCollection $addons,
        bool $first): array
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
            (($first === true) and ($subscription->wasImmediate() === true)))
        {
            // TODO: The amount may differ in the case of pro-rate.

            $mainLineItem = [
                // LineItem\Entity::NAME     => $plan->item->getName(),
                // LineItem\Entity::AMOUNT   => $plan->item->getAmount(),
                // LineItem\Entity::CURRENCY => $plan->item->getCurrency(),
                LineItem\Entity::ITEM_ID  => $plan->item->getPublicId(),
                LineItem\Entity::QUANTITY => $subscription->getQuantity(),
            ];

            $lineItems[] = $mainLineItem;
        }

        foreach ($addons as $addon)
        {
            //
            // Though line_item can get the item from the addon,
            // we don't do that, because addon is basically a ref for
            // line_item. All refs may not have an item associated with
            // it like addon does. Hence, line_item expects item_id
            // or item_input also to be sent in its input.
            //
            $addonLineItem = [
                LineItem\Entity::QUANTITY   => $addon->getQuantity(),
                LineItem\Entity::ITEM_ID    => $addon->item->getPublicId(),
                LineItem\Entity::REF        => $addon,
            ];

            $lineItems[] = $addonLineItem;
        }

        return $lineItems;
    }
}
