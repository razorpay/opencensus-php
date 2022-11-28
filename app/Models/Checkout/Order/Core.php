<?php

namespace RZP\Models\Checkout\Order;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Invoice\Entity as Invoice;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Methods\Entity as MethodsEntity;

class Core extends BaseCore
{
    use ValidatesAndAppliesOffer;

    /**
     * @param array $input
     *
     * @return Entity
     *
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    public function create(array $input): Entity
    {
        $checkoutOrder = new Entity();

        $checkoutOrder->build($input);

        $checkoutOrder->merchant()->associate($this->merchant);

        $this->validateOrderDetails($checkoutOrder, $input[Entity::AMOUNT]);

        $this->validateAndSetInvoiceDetailsIfApplicable($checkoutOrder);

        $this->validateAndApplyUPIOfferIfApplicable($checkoutOrder);

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

    public function markCheckoutOrderPaid(Entity $checkoutOrder): void
    {
        if ($checkoutOrder->isPaid())
        {
            return;
        }

        $checkoutOrder->setStatus(Status::PAID);
        $checkoutOrder->setCloseReason(CloseReason::PAID);
        $checkoutOrder->setClosedAt(Carbon::now()->getTimestamp());

        $this->repo->saveOrFail($checkoutOrder);
    }

    /**
     * Checks if the Order is associated with an Invoice.
     * Also, Validates & rejects requests if Partial/Subscription Payments are
     * being used with QrCode type checkout orders.
     *
     * @param Entity $checkoutOrder
     *
     * @return void
     *
     * @throws BadRequestValidationFailureException
     */
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
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws \Throwable
     */
    protected function validateOrderDetails(Entity $checkoutOrder, int $inputAmount): void
    {
        $orderId = $checkoutOrder->order_id;

        if (empty($orderId)) {
            if (
                $this->merchant->isTPVRequired() &&
                MethodsEntity::isTpvMethod($checkoutOrder->getMethod())
            ) {
                throw new BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                    Entity::ORDER_ID
                );
            }

            return;
        }

        $order = $this->repo->order->findByIdAndMerchant($orderId, $this->merchant);

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

        $checkoutOrder->order()->associate($order);
    }
}
