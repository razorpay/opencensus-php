<?php

namespace RZP\Models\Payment\Refund;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'amount'                => 'sometimes|integer|min:100',
        'notes'                 => 'sometimes|notes',
        'receipt'               => 'sometimes|string|max:40',
        'reverse_all'           => 'sometimes|boolean',
        'reversals'             => 'sometimes|array',
        'reversals.*.transfer'  => 'required',
        'reversals.*.amount'    => 'required|integer|min:100',
        'reversals.*.notes'     => 'sometimes|notes',
    ];

    protected static $createValidators = [
        'paymentStatus',
        'paymentRefundStatus',
        'refundAmount'
    ];

    protected static $directRules = [
        'payment_id'    => 'required',
        'amount'        => 'sometimes|integer|min:100',
        'notes'         => 'sometimes|notes',
        'receipt'       => 'sometimes|string|max:40',
    ];

    protected static $retryRules = [
        'bank_account' => 'sometimes|array',
    ];

    protected static $verifyInternalRefundGateways = [
        Payment\Gateway::HDFC,
        Payment\Gateway::AXIS_MIGS
    ];

    protected static $manualRefundGateways = [
        Payment\Gateway::HDFC,
        Payment\Gateway::BILLDESK,
        Payment\Gateway::AXIS_MIGS,
    ];

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

        if ((ctype_digit($amountToRefund) === false) and
            (is_int($amountToRefund) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount should be in paise and only have digits',
                Entity::AMOUNT);
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

    public static function validateVerifyInternalRefundAllowed(string $gateway)
    {
        if (in_array($gateway, self::$verifyInternalRefundGateways, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_GATEWAY);
        }
    }

    public static function validateManualGatewayRefundAllowed(string $gateway)
    {
        if (in_array($gateway, self::$manualRefundGateways, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_GATEWAY);
        }
    }

    public static function validateVerifyRefundAllowed($gateway)
    {
        if (in_array($gateway, Payment\Gateway::REFUND_RETRY_GATEWAYS, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY, 'gateway', $gateway);
        }
    }

    public function validateReversalsRequired(array $input)
    {
        if (isset($input['reversals']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'The reversals parameter is required for this refund request');
        }

        if (isset($input['amount']) === false)
        {
            return;
        }

        $reversalSum = 0;

        foreach ($input['reversals'] as $reversal)
        {
            $reversalSum += $reversal['amount'];
        }

        if ($reversalSum > $input['amount'])
        {
            throw new Exception\BadRequestValidationFailureException(
                'Sum of reversals provided is greater than the refund amount value',
                'amount');
        }
    }

    /**
     * If there is only one transfer, we support reversals, irrespective
     * of whether the refund is partial or full.
     * If there are multiple transfers, we support reversals ONLY IF
     * it's a full refund.
     *
     * @param string           $refundType
     * @param PublicCollection $transfers
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateReverseAll(string $refundType, PublicCollection $transfers)
    {
        $transferCount = $transfers->count();

        if (($transferCount > 1) and
            ($refundType === Payment\RefundStatus::PARTIAL))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The reverse_all parameter is not supported for this refund',
                'reverse_all',
                [
                    'transfer_count' => $transferCount,
                    'refund_type'    => $refundType,
                ]);
        }
    }
}
