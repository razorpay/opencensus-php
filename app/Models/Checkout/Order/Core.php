<?php

namespace RZP\Models\Checkout\Order;

use Carbon\Carbon;
use RZP\Constants\HashAlgo;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Invoice\Entity as Invoice;
use RZP\Models\Payment\Analytics\Metadata;
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

        if ($this->shouldValidateCheckoutSignature($input)) {
            $this->validateCheckoutSignature($input);
        }

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

    /**
     * Validates Checkout Signature with
     * HMAC of CheckoutId, Amount
     * Signature was generated by Checkout Service in Preferences API
     *
     *  We are passing signature as part of body and not header
     *  since payment/create/checkout route cannot pass headers
     *  so we are sending signature in body for payment ajax, checkout, qr routes
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateCheckoutSignature(array $input): void
    {
        $amount = $input['amount'] ?? 0;
        $checkoutSignature = $input['_']['checkout_signature'] ?? '';
        $checkoutId = $input['_']['checkout_id'] ?? '';

        $expectedSignature = hash_hmac(
            HashAlgo::SHA512,
            $checkoutId . '|' . $amount,
            $this->app['config']->get('applications.checkout_service.amount_signature_secret'),
        );

        if (!hash_equals($expectedSignature, $checkoutSignature)) {
            $this->trace->info(TraceCode::CREATE_CHECKOUT_ORDER_AMOUNT_SIGNATURE_MISMATCH, [
                'amount'            => $amount,
                'checkout_id'       => $checkoutId,
                'checkout_signature'=> $checkoutSignature,
            ]);

            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_AMOUNT_MISMATCH,
                Entity::AMOUNT
            );
        }
    }

    /**
     * Signature validation should be done only
     * - If library is checkoutjs i.e. standard checkout
     * - If checkout_version is present and is v1/v2
     * - If order_id/invoice_id is not present (if present, we already use amount from their entities)
     * - Exp is true
     *
     * We have added a new field checkout_version to support older merchants
     * who have hardcoded checkoutjs at their end, they do not send the new signature, version fields
     * So we do the signature validation only for the checkout version sent requests
     *
     * @param array $input
     * @return bool
     */
    protected function shouldValidateCheckoutSignature(array $input): bool
    {
        try
        {
            $library =  $input['_']['library'] ?? '';
            $checkoutVersion = $input['_']['checkout_version'] ?? '';
            if (
                (!Metadata::isStandardCheckoutLibrary($library)) ||
                (!in_array($checkoutVersion, array('v1', 'v2'), true)) ||
                (!empty($input['order_id'])) ||
                (!empty($input['invoice_id']))
            ) {
                return false;
            }

            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.checkout_order_signature_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $this->merchant->getId()]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            return $variant === 'variant_on';
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::CHECKOUT_ORDER_SIGNATURE_SPLITZ_REQUEST_FAILED
            );
        }

        return false;
    }
}
