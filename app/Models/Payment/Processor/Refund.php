<?php

namespace RZP\Models\Payment\Processor;

use BasicAuth;
use Mail;
use Request;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Gateway\Hdfc;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Batch;

trait Refund
{
    /**
     * Refunds a payment
     * @param  Payment\Entity   $payment     Payment Id
     * @param  array            $input  Refund input params
     * @param  Batch\Entity     $batch
     *
     * @return Payment\Refund\Entity
     */
    protected function refund(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        $refund = $this->buildRefundEntity($payment, $input, $batch);

        $this->processRefund($refund);

        return $refund;
    }

    public function verifyRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        // Currently doing it for only HDFC and PayTM. In case when other gateways
        // start getting similar issues, we will start supporting for them too.
        assert (($payment->getGateway() === Payment\Gateway::HDFC) or
                ($payment->getGateway() === Payment\Gateway::PAYTM));

        $data = array(
            'payment'   => $payment->toArray(),
            'refund'    => $refund->toArray(),
            'amount'    => $refund->getAmount());

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $refund->payment->card->toArray();
        }

        $msg = $this->mutex->acquireAndRelease($payment->getId(), function() use ($data, $payment, $refund)
        {
            $verify = $this->callGatewayForVerifyRefund($data);

            // Flag indicating if this is a buggy case fix.
            $this->verifyRefundStatus = $verify;

            $msg = 'Refund verification unsuccessful.';

            if ($verify === false)
            {
                $this->recordRefund(true);

                $this->trace->info(
                    TraceCode::VERIFY_REFUND_TRANSACTION_CREATED,
                    [
                        'payment_id'    => $payment->getId(),
                        'refund_id'     => $refund->getId(),
                    ]
                );

                //$this->sendRefundNotification($payment, $refund);

                $msg = 'Refund verification failed and Refund performed.';
            }
            else if ($verify === true)
            {
                $msg = 'Refund verified successfully.';
            }

            return $msg;
        });

        return ['verify_refund' => $msg];
    }

    public function manualGatewayRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        // Currently doing it for only HDFC. In case when other gateways start
        // getting similar issues, we will start supporting for them too.
        assert ($payment->getGateway() === Payment\Gateway::HDFC);

        // The refund should have already been successful and everything on the api side.
        assert ($refund->getTransactionId() !== null);

        // Just making sure that the payment also has the transaction id. Refund will not have a transaction
        // if payment does not have a transaction, anyway.
        assert ($payment->getTransactionId() !== null);

        // The payment should have been captured. Otherwise, refund transaction should not have been created.
        // Though, there are some edge cases where refund transaction was created even though the payment has not
        // been captured. Check PR #905 and #909.
        assert (($payment->hasBeenCaptured() === true) or
                (in_array($payment->card->getNetworkCode(),
                    [Card\Network::MAES, Card\Network::RUPAY, Card\Network::DICL]) === true));

        $data = array(
            'payment'   => $payment->toArray(),
            'refund'    => $refund->toArray(),
            'amount'    => $refund->getAmount());

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $payment->card->toArray();
        }

        // The reason for NOT using verifyRefund Gateway function is because in ManualRefund, we want to add
        // more checks and validations in the gateway function. VerifyRefund takes care of the checks specific
        // to verifyRefund only. Since manualRefund is a very exceptional case and hopefully a one-time execution,
        // we want to add more asserts around it.
        $manualGatewayRefundResult = $this->callGatewayForManualRefund($data);

        // Here, $manualGatewayRefundResult=true means that the payment is refunded on the gateway side.
        if ($manualGatewayRefundResult === true)
        {
            $msg = 'Successfully created a refund on gateway';
        }
        else if ($manualGatewayRefundResult === false)
        {
            $msg = 'DID NOT CREATE A REFUND ON GATEWAY. ISSUE!';
        }
        else
        {
            $msg = 'THIS IS UNEXPECTED!';
        }

        return [
            'manual_gateway_refund' => $msg,
            'payment_id'            => $payment->getId(),
            'refund_id'             => $refund->getId(),
        ];
    }

    protected function setPaymentAndRefundInfo($refund, $payment)
    {
        $this->merchant = $payment->merchant;

        $this->methods = $payment->merchant->methods;

        $this->refund = $refund;

        $this->payment = $payment;
    }

    /**
     * Sends out refund related notifications
     * To 3 places in total:
     *
     * - Dashboard (for analytics)
     * - Slack (for us to see)
     * - EMails (to both customer and merchant)
     * @param  Payment\Entity        $payment Payment Entity
     * @param  Payment\Refund\Entity $refund  Refund Entity
     * @return null
     */
    protected function sendRefundNotification(
        Payment\Entity $payment,
        Payment\Refund\Entity $refund)
    {
        //
        // Analytics is on dashboard side for now
        //
        $notifier = new Notify($payment);
        $notifier->addRefund($refund);
        $notifier->trigger(Notify::REFUNDED);

        $this->notifyDashboard('refund', $this->refund);
    }

    public function refundAuthorizedPayment(Payment\Entity $payment, array $input = [])
    {
        $this->setPayment($payment);

        if ($this->payment->isAuthorized() === false)
        {
            throw new Exception\InvalidArgumentException(
                'Can only refund authorized payments here but ' .
                'the status is ' . $payment->getStatus());
        }

        // For now allow refunding authorized payments immediately.
        // $days = 5;

        // if ($this->payment->getDaysSinceAuthorized() <= $days)
        // {
            if ((isset($input['force'])) and
                ($input['force'] === '1'))
            {
                unset($input['force']);
            }
        //     else
        //     {
        //         throw new Exception\BadRequestValidationFailureException(
        //             'The authorized payment is not older than: ' . $days . ' days');
        //     }
        // }

        return $this->refund($payment, $input);
    }

    protected function refundCapturedPayment($payment, array $input = [], Batch\Entity $batch = null)
    {
        if ($payment->isFullyRefunded())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED);
        }

        if ($payment->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        return $this->refund($payment, $input, $batch);
    }

    public function refundPaymentViaMerchant($paymentId, $input)
    {
        $payment = $this->retrieve($paymentId);

        return $this->refundCapturedPayment($payment, $input);
    }

    public function refundPaymentViaBatchEntry(Payment\Entity $payment, Batch\Entity $batch, $amount)
    {
        $merchant = $batch->merchant;

        //
        // Check if a refund already exists.
        // If one exists, then we should not fire a new one else two refunds will happen.
        //
        $refund = $this->findExistingRefundForBatch($batch, $payment);

        if ($refund !== null)
        {
            return $refund;
        }

        $input = ['amount' => (string) $amount];

        // No refund existed so fire a new one.
        return $this->refundCapturedPayment($payment, $input, $batch);
    }

    protected function callGatewayForVerifyRefund($data)
    {
        $verifyRefundResult = null;

        try
        {
            $verifyRefundResult = $this->callGatewayFunction(Payment\Action::VERIFY_REFUND, $data);
        }
        catch (Exception\BaseException $e)
        {
            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_VERIFY_REFUND_FAILURE);

            throw $e;
        }

        return $verifyRefundResult;
    }

    protected function callGatewayForManualRefund($data)
    {
        $manualGatewayRefundResult = null;

        $this->trace->info(
            TraceCode::MANUAL_GATEWAY_REFUND_INITIATED,
            [
                'payment_id'    => $data['payment']['id'],
                'refund_id'     => $data['refund']['id'],
            ]
        );

        try
        {
            $manualGatewayRefundResult = $this->callGatewayFunction(Payment\Action::MANUAL_GATEWAY_REFUND, $data);
        }
        catch (Exception\BaseException $ex)
        {
            $this->tracePaymentFailed(
                $ex->getError(),
                TraceCode::MANUAL_GATEWAY_REFUND_FAILURE
            );

            throw $ex;
        }

        return $manualGatewayRefundResult;
    }

    protected function refundOnGateway($data)
    {
        try
        {
            $this->callGatewayFunction(Payment\Action::REFUND, $data);
        }
        catch (Exception\BaseException $e)
        {
            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REFUND_FAILURE);

            throw $e;
        }
    }

    protected function recordRefund($forceRefundTransaction = false)
    {
        $this->repo->transaction(function() use ($forceRefundTransaction)
        {
            $payment = $this->payment;

            $this->paymentRepo->lockForUpdate($payment->getKey());

            $this->createTransactionForRefund($this->refund, $payment, $forceRefundTransaction);

            $this->updatePaymentRefunded();

            $this->repo->saveOrFail($this->payment);
            $this->repo->saveOrFail($this->refund);
        });
    }

    protected function buildRefundEntity(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        $this->trace->info(
            TraceCode::PAYMENT_REFUND_REQUEST,
            [
                'payment_id' => $payment->getId(),
                'input' => $input
            ]);

        $this->setPayment($payment);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        if ($this->payment->isCaptured())
        {
            $this->validateMerchantBalance($refund);
        }

        $this->refund = $refund;

        $refund->batch()->associate($batch);

        return $refund;
    }

    protected function processRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $data = array(
            'payment'   => $payment->toArray(),
            'refund'    => $refund->toArray(),
            'amount'    => $refund->getAmount());

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $refund->payment->card->toArray();
        }

        $this->mutex->acquireAndRelease($payment->getId(), function() use ($data, $payment, $refund)
        {
            if (($payment->getTransactionId() !== null) or
                ($payment->isAuthorized() === false))
            {
                $this->refundOnGateway($data);
            }

            $this->recordRefund();

            $this->sendRefundNotification($payment, $refund);
        });

        return $refund;
    }

    protected function updatePaymentRefunded()
    {
        // Indicates buggy case where refund entity is already present
        if ($this->verifyRefundStatus === false)
        {
            ; // No action required here.
        }
        else
        {
            $this->payment->refundAmount($this->refund->getAmount());
        }

        $this->tracePaymentInfo(TraceCode::PAYMENT_REFUND_SUCCESS);
    }

    protected function validateMerchantBalance($refund)
    {
        $merchant = $refund->merchant;

        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);

        if ($balance->getBalance() < $refund->getAmount())
        {
            $this->trace->info(
                TraceCode::PAYMENT_REFUND_FAILURE,
                [
                    'message' => 'Not enough balance',
                    'merchant_balance' => $balance->getBalance(),
                    'refund_amount' => $refund->getAmount()
                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE);
        }
    }

    /**
     * @param Payment\Refund\Entity $refund
     * @param Payment\Entity $payment
     * @param bool $forceRefundTransaction This param is used for when we don't want to check for captured payment
     *                                      for authAndCapture supported gateways before creating a refund transaction
     *
     * @return null|Transaction\Entity
     * @throws Exception\LogicException
     */
    public function createTransactionForRefund(
        Payment\Refund\Entity $refund, Payment\Entity $payment, $forceRefundTransaction = false)
    {
        $gateway = $payment->getGateway();

        assert ($refund->getTransactionId() === null);

        // For authAndCapture supported gateways, payment transaction is created only after capture.
        // For gateways which don't support authAndCapture, payment transaction is created after authorization.
        // There are a few gateways which are partially authAndCapture gateways. This means that for
        // some networks, payment transaction is created at authorization and for some networks, payment
        // transaction is created at capture.

        // Hence, for [notAuthAndCapture] and [notAuthAndCaptureForSpecificNetworks] gateways,
        // we do not check for the capture timestamp.
        // For [authAndCapture] gateways, we check for capture timestamp.

        $networkCode = null;
        $paymentCard = $payment->card;

        // If payment method is wallet or net banking.
        if ($paymentCard !== null)
        {
            $networkCode = $paymentCard->getNetworkCode();
        }

        $supportsAuthAndCapture = Payment\Gateway::supportsAuthAndCapture($gateway, $networkCode);

        if ((($supportsAuthAndCapture === true) and ($payment->getCaptureTimestamp() !== null)) or
            ($supportsAuthAndCapture === false) or
            ($forceRefundTransaction === true))
        {
            if ($payment->transaction === null)
            {
                throw new Exception\LogicException(
                    'Transaction expected but not present for payment: ' . $payment->getId());
            }

            $txn = (new Transaction\Core)->createFromRefund($refund);

            $this->repo->saveOrFail($txn);

            return $txn;
        }

        return null;
    }

    protected function findExistingRefundForBatch(Batch\Entity $batch, Payment\Entity $payment)
    {
        // This ensure that if that batch entity is already processed, we update the refund id
        $refunds = $this->repo->refund->fetchRefundsByBatchAndPayment($batch, $payment);

        $count = count($refunds);

        if ($count > 0)
        {
            $this->trace->error (
                TraceCode::BATCH_ALREADY_PROCESSED,
                [
                    'message' => 'Batch entry already processed',
                    'batch'   => $batch->getId(),
                    'refunds' => $refunds->toArrayPublic()
                ]);

            assert($count === 1);

            return $refunds[0];
        }

        return null;
    }
}
