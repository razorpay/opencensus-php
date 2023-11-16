<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Order;
use RZP\Models\Feature as Features;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Trait SplitPayment
 * @package RZP\Models\Payment\Processor
 *
 * @property Merchant\Entity $merchant
 */
trait SplitPayment
{

    private function validateSplitPayment(array $input)
    {
        // Skip validations, since split payment fields are not passed at all.
        if (isset($input[Payment\Entity::WALLET_AMOUNT]) === false)
        {
            return false;
        }

        if ($this->merchant->isFeatureEnabled(Features\Constants::RAZORPAY_WALLET) === false)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_FEATURE_DISABLED, [
                'merchant_id' => $this->merchant->getId()
            ]);
            throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }

        // validate request is coming from checkout
        $currentRouteName = $this->route->getCurrentRouteName();
        if ($this->route->isSplitPaymentRoute($currentRouteName) === false)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_ROUTE_NOT_ALLOWED, [
                'route' => $currentRouteName
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_NOT_ALLOWED, null,
                [
                    'route' => $currentRouteName,
                ]);
        }

        if ($this->merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_FEE_BEARER_ENABLED, [
                'merchant_id' => $this->merchant->getId()
            ]);
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_NOT_ALLOWED);
        }

        //Validate order is created
        if ($this->order === null)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_ORDER_NOT_CREATED, [
                'input' => $input
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                Payment\Entity::ORDER_ID,
                [],
                "Order Id is required for split payment");
        }

        $orderAmount = $this->order->getAmount();
        $paidAmount = $this->order->getAmountPaid();

        // if paid_amount > 0, split payment cannot be supported
        if ($paidAmount > 0)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_ORDER_ALREADY_PAID, [
                'order_id'    => $this->order->getId(),
                'amount_paid' => $paidAmount
            ]);
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID);
        }

        // validate break of amount, wallet_amount is correct
        $totalPaymentAmount = $input[Payment\Entity::AMOUNT] + $input[Payment\Entity::WALLET_AMOUNT];
        if ($totalPaymentAmount !== $orderAmount)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_REQUEST_INVALID, [
                'input' => $input
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_ORDER_AMOUNT_MISMATCH,
                Payment\Entity::WALLET_AMOUNT,
                [
                    'amount'        => $input[Payment\Entity::AMOUNT],
                    'wallet_amount' => $input[Payment\Entity::WALLET_AMOUNT],
                ]);
        }

        $properties = [
            'id'            => $this->merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.split_payment_enabled_experiment_id'),
        ];

        $splitPaymentEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enabled');

        if ($splitPaymentEnabled === false)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_EXPERIMENT_DISABLED, [
                'merchant_id' => $this->merchant->getId()
            ]);
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_NOT_ALLOWED);
        }

        return true;
    }

    private function processSplitPayment(array $input, array $paymentData)
    {
        try
        {
            $this->trace->info(
                TraceCode::CREATE_SPLIT_PAYMENT_PROCESSING_INITIATED,
                [
                    'input' => $input
                ]
            );
            $this->trace->count(Payment\Metric::SPLIT_PAYMENT_REQUEST_COUNT);

            $startAt = Carbon::now()->getTimestamp();

            // creates empty order_meta
            $orderMetaCore = (new Order\OrderMeta\Core);
            $orderMetaCore->createOrderMeta($this->order, [
                Order\OrderMeta\Entity::TYPE     => Order\OrderMeta\Type::SPLIT_PAYMENT_INFO,
                Order\OrderMeta\Entity::ORDER_ID => $this->order->getId(),
                Order\OrderMeta\Entity::VALUE    => [],
            ]);

            // creates wallet payment input and calls processor
            $input = $this->buildWalletSplitPaymentInput($input);
            $walletPaymentData = $this->process($input);

            $paymentId = $paymentData['payment_id'] ?? $paymentData['razorpay_payment_id'] ?? '';
            $paymentId = Payment\Entity::stripSignWithoutValidation($paymentId);
            if (empty($paymentId) === true)
            {
                $this->trace->error(TraceCode::SPLIT_PAYMENT_DECODE_PAYMENT_ID_FAILED);
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED
                );
            }

            $walletPaymentId = $walletPaymentData['payment_id'] ?? $walletPaymentData['razorpay_payment_id'] ?? '';
            $walletPaymentId = Payment\Entity::stripSignWithoutValidation($walletPaymentId);
            if (empty($walletPaymentId) === true)
            {
                $this->trace->error(TraceCode::SPLIT_PAYMENT_DECODE_PAYMENT_ID_FAILED);
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED
                );
            }

            // Store pay_other <=> pay_wallet relation
            (new Order\OrderMeta\Core)->addRelationToSplitPaymentMeta($this->order, [
                $paymentId => $walletPaymentId
            ]);

            $duration = Carbon::now()->getTimestamp() - $startAt;
            $this->trace->count(Payment\Metric::SPLIT_PAYMENT_CREATED_COUNT);
            $this->trace->histogram(Payment\Metric::SPLIT_PAYMENT_REQUEST_TIME, $duration);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Payment\Metric::SPLIT_PAYMENT_FAILED_COUNT);
            $this->trace->error(TraceCode::CREATE_SPLIT_PAYMENT_PROCESSING_FAILED,
                [
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTrace(),
                    'input' => $input,
                ]
            );
            throw $e;
        }
    }

    public function buildWalletSplitPaymentInput(array $input)
    {
        $walletPaymentInput = [];

        // set required fields in input.
        $walletPaymentInput['_'] = $input['_'];
        $walletPaymentInput[Payment\Entity::METHOD] = Payment\Entity::WALLET;
        $walletPaymentInput[Payment\Entity::WALLET] = Wallet::RAZORPAYWALLET;
        $walletPaymentInput[Payment\Entity::EMAIL] = $input[Payment\Entity::EMAIL];
        $walletPaymentInput[Payment\Entity::CONTACT] = $input[Payment\Entity::CONTACT];
        $walletPaymentInput[Payment\Entity::ORDER_ID] = $input[Payment\Entity::ORDER_ID];
        $walletPaymentInput[Payment\Entity::CURRENCY] = $input[Payment\Entity::CURRENCY];
        $walletPaymentInput[Payment\Entity::SPLIT_AMOUNT] = $input[Payment\Entity::AMOUNT];
        $walletPaymentInput[Payment\Entity::AMOUNT] = $input[Payment\Entity::WALLET_AMOUNT];
        $walletPaymentInput[Payment\Entity::WALLET_USER_ID] = $input[Payment\Entity::WALLET_USER_ID];
        $walletPaymentInput[Payment\Entity::NOTES] = $input[Payment\Entity::NOTES];

        return $walletPaymentInput;
    }

    public function fetchSplitPaymentFromOrderMeta(Payment\Entity $payment)
    {
        $this->trace->info(TraceCode::SPLIT_PAYMENT_META_FETCH_PAYMENT, [
            'payment_id' => $payment->getId()
        ]);

        $splitPaymentMeta = (new Order\OrderMeta\Core)->getSplitPaymentMeta($payment->order);
        if (empty($splitPaymentMeta) == true)
        {
            $this->trace->error(TraceCode::SPLIT_PAYMENT_META_NOT_FOUND, [
                'payment_id' => $payment->getId()
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED,
                null,
                null,
                'Wallet payment failed. Please try again later.'
            );
        }

        $valueJson       = $splitPaymentMeta->getValue();
        $walletPaymentId = $valueJson['relations'][$payment->getId()];

        if (empty($walletPaymentId) === true) {
            $this->trace->error(TraceCode::SPLIT_PAYMENT_META_RELATION_NOT_FOUND, [
                'meta' => $splitPaymentMeta
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED,
                null,
                null,
                'Wallet payment failed. Please try again later.'
            );
        }

        return $this->repo->payment->findOrFail($walletPaymentId);
    }

    public function refundSplitPayments(Payment\Entity $payment)
    {
        if ($payment->isFailed() === false)
        {
            $this->trace->info(TraceCode::REFUND_SPLIT_PAYMENT_INVALID_PAYMENT, [
                'payment_id' => $payment->getId()
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'This payment is not failed.'
            );
        }

        if ($payment->isSplitPayment() === false)
        {
            $this->trace->info(TraceCode::REFUND_SPLIT_PAYMENT_INVALID_PAYMENT, [
                'payment_id' => $payment->getId()
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED,
                null,
                null,
                'This order doesnt have any associated split payments.'
            );
        }

        // Terminating condition to avoid recursive calls.
        if ($payment->isRazorpaywalletPayment() === true)
        {
            return;
        }

        try
        {
            $this->trace->info(TraceCode::REFUND_SPLIT_PAYMENT_INITIATED, [
                'payment_id' => $payment->getId()
            ]);
            $this->trace->count(Payment\Metric::REFUND_SPLIT_PAYMENT_COUNT);
            $startTime = Carbon::now()->getTimestamp();

            $walletPayment = $this->fetchSplitPaymentFromOrderMeta($payment);
            if ($walletPayment === null)
            {
                $this->trace->info(TraceCode::SPLIT_PAYMENT_META_RELATION_NOT_FOUND, [
                    'order_id' => $this->order->getId()
                ]);
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED,
                    null,
                    null,
                    'Wallet payment failed. Please try again later.'
                );
            }

            $this->refundAuthorizedPayment($walletPayment);

            $duration = Carbon::now()->getTimestamp() - $startTime;
            $this->trace->count(Payment\Metric::REFUND_SPLIT_PAYMENT_PROCESSED_COUNT);
            $this->trace->histogram(Payment\Metric::REFUND_SPLIT_PAYMENT_REQUEST_TIME, $duration);
            $this->trace->info(TraceCode::REFUND_SPLIT_PAYMENT_PROCESSED, [
                'payment_id' => $walletPayment->getId()
            ]);
        }
        catch (\Exception $e)
        {
            $this->trace->count(Payment\Metric::REFUND_SPLIT_PAYMENT_FAILED_COUNT);
            $this->trace->error(TraceCode::REFUND_SPLIT_PAYMENT_FAILED, [
                'message'     => $e->getMessage(),
                'stack_trace' => $e->getTrace(),
                'payment'     => $payment
            ]);
            throw $e;
        }
    }

    public function processAutoCaptureForSplitPayment(Payment\Entity $payment)
    {
        // return if payment isn't a split payment
        if ($payment->isSplitPayment() === false)
        {
            $this->trace->error(TraceCode::AUTO_CAPTURE_SPLIT_PAYMENT_INVALID_PAYMENT, [
                'payment_id' => $payment->getId()
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED,
                null,
                null,
                'This order doesnt have any associated split payments.'
            );
        }

        // check payment in captured state
        if ($payment->isCaptured() === false)
        {
            $this->trace->error(TraceCode::AUTO_CAPTURE_SPLIT_PAYMENT_INVALID_PAYMENT, [
                'payment_id' => $payment->getId()
            ]);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED,
                null,
                null,
                'Payment is not captured.'
            );
        }

        // Process auto capture only when primary payment is being captured
        if ($payment->isRazorpaywalletPayment() === true)
        {
            $this->trace->info(TraceCode::SPLIT_PAYMENT_SKIPPING_AUTO_CAPTURE, [
                'payment' => $payment
            ]);
            return;
        }

        try
        {
            $startTime = Carbon::now()->getTimestamp();
            $this->trace->count(Payment\Metric::AUTO_CAPTURE_SPLIT_PAYMENT_COUNT);
            $this->trace->info(TraceCode::SPLIT_PAYMENT_AUTO_CAPTURE_INITIATED, [
                'payment' => $payment
            ]);

            $walletPayment = $this->fetchSplitPaymentFromOrderMeta($payment);

            if ($walletPayment === null)
            {
                $this->trace->info(TraceCode::SPLIT_PAYMENT_META_RELATION_NOT_FOUND, [
                    'order_id' => $this->order->getId()
                ]);
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_FAILED,
                    null,
                    null,
                    'Wallet payment failed. Please try again later.'
                );
            }

            $this->setPayment($walletPayment);
            $this->capturePayment($walletPayment, $walletPayment->getAmount(), $walletPayment->getCurrency());

            $duration = Carbon::now()->getTimestamp() - $startTime;
            $this->trace->count(Payment\Metric::AUTO_CAPTURE_SPLIT_PAYMENT_PROCESSED_COUNT);
            $this->trace->histogram(Payment\Metric::AUTO_CAPTURE_SPLIT_PAYMENT_REQUEST_TIME, $duration);
            $this->trace->info(TraceCode::AUTO_CAPTURE_SPLIT_PAYMENT_PROCESSED, [
                'payment_id' => $walletPayment->getId()
            ]);
        }
        catch (\Exception $e)
        {
            $this->trace->count(Payment\Metric::AUTO_CAPTURE_SPLIT_PAYMENT_FAILED_COUNT);
            $this->trace->error(TraceCode::SPLIT_PAYMENT_CAPTURE_FAILED, [
                'message'        => $e->getMessage(),
                'stack_trace'    => $e->getTrace(),
                'payment'        => $payment
            ]);
            throw $e;
        }
    }
}
