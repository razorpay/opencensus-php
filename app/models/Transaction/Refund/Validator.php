<?php

namespace Models\Transaction\Refund;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Transaction;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        => 'sometimes|numeric|min:1');

    protected static $createValidators = array(
        'transactionStatus',
        'transactionRefundStatus',
        'refundAmount');

    protected $txn;

    public function setTransaction($txn)
    {
        $this->txn = $txn;
    }

    protected function validateTransactionStatus($input)
    {
        if ($this->txn->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_TRANSACTION_STATUS_NOT_CAPTURED);
        }
    }

    protected function validateTransactionRefundStatus($input)
    {
        if ($this->txn->isFullyRefunded())
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_TRANSACTION_FULLY_REFUNDED);
        }
    }

    protected function validateRefundAmount($input)
    {
        if (isset($input['amount']) === false)
        {
            return;
        }

        $txn = $this->txn;

        $amountToRefund = $input['amount'];

        $amountCaptured = $txn->getAmount();

        // Although both these checks could be combined,
        // it's done separately to give better error message
        // for following two scenarios
        if ($amountToRefund > $amountCaptured)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($amountToRefund > $txn->getAmountUnrefunded())
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED);
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestException($messages);
    }
}