<?php

namespace RZP\Models\Payment\Processor;

use BasicAuth;
use RZP\Constants\Mode;
use RZP\Http\Route;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Mail;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use Request;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Gateway\Hdfc;
use RZP\Models\Base\Lock;

trait Refund
{
    /**
     * Refunds a payment
     * @param  string   $id     Payment Id
     * @param  array    $input  Refund input params
     *
     * @return Payment\Refund\Entity
     */
    protected function refund($id, $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_REFUND_REQUEST,
            ['id' => $id, 'input' => $input]);

        $payment = $this->retrieve($id);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        if ($this->payment->isCaptured())
        {
            $this->validateMerchantBalance($refund);
        }

        $this->lockPayment($payment);

        $this->refund = $refund;

        $data = array(
            'payment'   => $payment->toArray(),
            'refund'    => $refund->toArray(),
            'amount'    => $refund->getAmount());

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $refund->payment->card->toArray();
        }

        if (($payment->getTransactionId() !== null) or
            ($payment->isAuthorized() === false))
        {
            $this->callGatewayForRefund($data);
        }

        $this->recordRefund();

        $this->sendRefundNotification($payment, $refund);

        return $refund;
    }

    public function verifyRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        // Currently doing it for only HDFC. In case when other gateways start
        // getting similar issues, we will start supporting for them too.
        assert ($payment->getGateway() === Payment\Gateway::HDFC);

        $data = array(
            'payment'   => $payment->toArray(),
            'refund'    => $refund->toArray(),
            'amount'    => $refund->getAmount());

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $refund->payment->card->toArray();
        }

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

    public function refundAuthorizedPayment($id, $input)
    {
        $payment = $this->retrieve($id);

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

        return $this->refund($id, $input);
    }

    public function refundCapturedPayment($id, $input)
    {
        $payment = $this->retrieve($id);

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

        return $this->refund($id, $input);
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

    protected function callGatewayForRefund($data)
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
        finally
        {
            $this->lock->release($this->payment->getId());
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
}
