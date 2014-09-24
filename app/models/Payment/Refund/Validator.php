<?php

namespace Models\Payment\Refund;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        => 'sometimes|numeric|min:1');

    protected static $createValidators = array(
        'paymentStatus',
        'paymentRefundStatus',
        'refundAmount');

    protected $payment;

    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    protected function validatePaymentStatus($input)
    {
        if ($this->payment->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }
    }

    protected function validatePaymentRefundStatus($input)
    {
        if ($this->payment->isFullyRefunded())
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED);
        }
    }

    protected function validateRefundAmount($input)
    {
        if (isset($input['amount']) === false)
        {
            return;
        }

        $payment = $this->payment;

        $amountToRefund = $input['amount'];

        $amountCaptured = $payment->getAmount();

        // Although both these checks could be combined,
        // it's done separately to give better error message
        // for following two scenarios
        if ($amountToRefund > $amountCaptured)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($amountToRefund > $payment->getAmountUnrefunded())
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED);
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestException($messages);
    }
}