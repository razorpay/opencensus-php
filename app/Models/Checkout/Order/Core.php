<?php

namespace RZP\Models\Checkout\Order;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Invoice\Entity as Invoice;
use RZP\Models\Order\Entity as OrderEntity;
use RZP\Trace\TraceCode;

class Core extends BaseCore
{
    public function create(array $input): Entity
    {
        $checkoutOrder = new Entity();

        $checkoutOrder->build($input);

        $checkoutOrder->merchant()->associate($this->merchant);

        $this->validateOrderDetails($checkoutOrder, $input[Entity::AMOUNT]);

        $this->validateAndSetInvoiceDetailsIfApplicable($checkoutOrder);

        $this->repo->saveOrFail($checkoutOrder);

        return $checkoutOrder;
    }

    /**
     * @param Entity $checkoutOrder
     *
     * @return array
     */
    public function getPaymentArrayFromCheckoutOrder(Entity $checkoutOrder): array
    {
        $paymentArray = [];

        $checkoutOrderArray = $checkoutOrder->toArrayPrivate();
        // Make all metadata keys as root level keys
        $checkoutOrderArray = array_merge($checkoutOrderArray, $checkoutOrderArray[Entity::META_DATA]);
        // Remove 'meta_data' key as it's contents have been promoted to root level
        unset($checkoutOrderArray[Entity::META_DATA]);
        // Convert id's to public id's i.e. append entity name to id
        $checkoutOrder->setPublicAttributes($checkoutOrderArray);

        foreach (Entity::CREATE_PAYMENT_ATTRIBUTES as $attributeKey)
        {
            if (isset($checkoutOrderArray[$attributeKey]))
            {
                $paymentArray[$attributeKey] = $checkoutOrderArray[$attributeKey];
            }
        }

        return $paymentArray;
    }

    protected function validateAndSetInvoiceDetailsIfApplicable(Entity $checkoutOrder): void
    {
        if ($checkoutOrder->order === null) {
            return;
        }

        /** @var ?Invoice $invoice */
        $invoice = $checkoutOrder->order->invoice()->withTrashed()->first();

        if ($invoice === null)
        {
            return;
        }

        if ($checkoutOrder->isQrCodeOrder()) {
            $isPartialPayment = $invoice->getAmount() !== $checkoutOrder->getAmount();

            if ($isPartialPayment) {
                throw new BadRequestValidationFailureException(
                    'Partial payments are not allowed for QR Code Checkout Orders'
                );
            }

            if ($invoice->hasSubscription()) {
                throw new BadRequestValidationFailureException(
                    'Subscription/Recurring payments are not allowed for QR Code Checkout Orders'
                );
            }
        }

        $checkoutOrder->invoice()->associate($invoice);
    }

    /**
     * Validates if Order associated to CheckoutOrder is already paid and checks
     * if amount passed in the input is different from amount due on the order.
     *
     * @param Entity $checkoutOrder The CheckoutOrder entity that is being created
     * @param int    $inputAmount   The amount passed in the input by the consumer
     *
     * @return void
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateOrderDetails(Entity $checkoutOrder, int $inputAmount): void
    {
        $order = $checkoutOrder->order;

        if ($order === null) {
            return;
        }

        if ($order->isPaid()) {
            throw new BadRequestValidationFailureException(
                'Order already paid. Cannot create CheckoutOrder on paid orders.',
                Entity::ORDER_ID,
                [Entity::ORDER_ID => $order->getId()]
            );
        }

        if ($order->getAmountDue() !== $inputAmount) {
            $this->trace->info(TraceCode::INPUT_AMOUNT_DIFFERENT_THAN_ORDER_AMOUNT, [
                'checkout_order_id' => $checkoutOrder->getId(),
                'order_id' => $order->getId(),
                'order_amount_due' => $order->getAmountDue(),
                'input_amount' => $inputAmount,
            ]);
        }
    }
}
