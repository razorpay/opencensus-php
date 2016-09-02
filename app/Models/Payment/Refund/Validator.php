<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        => 'sometimes|integer|min:100',
        'notes'         => 'sometimes|notes'
    );

    protected static $createValidators = array(
        'paymentStatus',
        'paymentRefundStatus',
        'refundAmount'
    );

    protected $payment;

    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    protected function validatePaymentStatus()
    {
        if (($this->payment->isCaptured() === false) and
            ($this->payment->isAuthorized() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }
    }

    protected function validatePaymentRefundStatus()
    {
        if ($this->payment->isFullyRefunded())
        {
            throw new Exception\BadRequestException(
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

        if (empty($amountToRefund) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount cannot be blank',
                'amount');
        }

        if (ctype_digit($amountToRefund) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount should only have digits',
                'amount');
        }

        $amountCaptured = $payment->getAmount();

        // Although both these checks could be combined,
        // it's done separately to give better error message
        // for following two scenarios
        if ($amountToRefund > $amountCaptured)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($amountToRefund > $payment->getAmountUnrefunded())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED);
        }
    }
}
