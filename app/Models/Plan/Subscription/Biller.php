<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\LineItem;
use RZP\Trace\TraceCode;

/**
 * Takes care of billing life-cycle of a subscription which can include
 * creating invoice and creating a corresponding payment.
 * First time invoice creation for a subscription when auth is being done
 * is a special case.
 */
class Biller extends Base\Core
{
    public function createInvoiceAndCharge(Entity $subscription)
    {
        $data = $this->createInvoiceBeforeCharge($subscription);

        if ($data['activated'] === true)
        {
            //
            // Might have to fire a webhook in sync --
            // otherwise charge webhook might go before this
            // since our queue doesn't maintain order.
            //
            (new Core)->fireWebhookForStatusUpdate($subscription, Status::ACTIVE);
        }

        $invoice = $data['invoice'];

        //
        // We should not charge any invoice which is in halted status,
        // since, the subscription would also be in halted status here.
        // We do not charge halted subscriptions, we only create an invoice.
        //
        if ($invoice->getSubscriptionStatus() === Invoice\Status::HALTED)
        {
            $this->trace->info(
                TraceCode::SUBSCRIPTION_INVOICE_HALTED,
                [
                    'invoice_id'        => $invoice->getId(),
                    'subscription_id'   => $subscription->getId(),
                ]);

            //
            // We need to update the charge_at of the subscription so that the
            // flow continues as it is even if the subscription is in halted state.
            //
            (new Charge)->updateNextRunAtForSubscription($subscription);

            return;
        }

        (new Core)->charge($subscription, $invoice);
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

    protected function createInvoiceBeforeCharge(Entity $subscription): array
    {
        return $this->repo->transaction(
            function() use ($subscription)
            {
                $activated = false;

                //
                // If first charge, we set the status to active.
                // If not, the status would already be active or
                // would be reset by some other flow (auth/capture).
                //
                if ($subscription->getPaidCount() === 0)
                {
                    $this->activateSubscription($subscription);

                    $activated = true;
                }

                $addons = $this->repo->addon->getUnusedAddonsForSubscription($subscription);

                $invoice = $this->createInvoiceForSubscription($subscription, $addons);

                return ['invoice' => $invoice, 'activated' => $activated];
            });
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

        $subscription->setStatus(Status::ACTIVE);
        $this->repo->saveOrFail($subscription);
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
            (($first === true) and ($subscription->getStartAt() === null)))
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
