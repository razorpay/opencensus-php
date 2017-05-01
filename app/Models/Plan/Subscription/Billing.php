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
use RZP\Models\Item;
use RZP\Models\AddOn;
use RZP\Models\Schedule;
use RZP\Models\Schedule\Task;
use RZP\Trace\TraceCode;

/**
 * Takes care of billing life-cycle of a subscription which can include
 * creating invoice and creating a corresponding payment.
 * First time invoice creation for a subscription when auth is being done
 * is a special case.
 */
class Billing extends Base\Core
{
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
}
