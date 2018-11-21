<?php

namespace RZP\Models\Payment\Refund;

use App;
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

    protected static $editStatusRules = [
        Entity::STATUS          => 'required|string|custom',
        Entity::REFERENCE1      => 'sometimes|string|max:255',
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

    protected static $createValidators = [
        'paymentStatus',
        'paymentRefundStatus',
        'refundAmount'
    ];

    protected static $retryBulkRules = [
        'refund_ids'    => 'required|sequential_array|max:1000',
        'refund_ids.*'  => 'required|public_id'
    ];

    protected static $directRetryBulkRules = [
        'refund_ids'    => 'required|sequential_array|max:1000',
        'refund_ids.*'  => 'required|public_id'
    ];

    protected static $markProcessedBulkRules = [
        'refund_ids'    => 'required|sequential_array|max:1000',
        'refund_ids.*'  => 'required|public_id',
    ];

    protected static $customerRefundDetailsRules = [
        'refund_id'         => 'required_without_all:payment_id,reservation_id|public_id',
        'payment_id'        => 'required_without_all:refund_id,reservation_id|public_id',
        'reservation_id'    => 'required_without_all:payment_id,refund_id|string|max:50',
        'mode'              => 'sometimes|in:live,test',
        'captcha'           => 'required|string|custom',
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

    protected static $scroogeGatewayRefundRules = [
        'id'                    => 'required|unsigned_id',
        'merchant_id'           => 'required|unsigned_id',
        'payment_id'            => 'required|unsigned_id',
        'currency'              => 'required|string|size:3',
        'gateway'               => 'required|string',
        'amount'                => 'required|integer|min:100',
        'base_amount'           => 'required|integer|min:100',
        'method'                => 'required|string',
        'payment_amount'        => 'required|integer|min:100',
        'payment_base_amount'   => 'required|integer|min:100',
        'payment_created_at'    => 'required|epoch',
        'attempts'              => 'sometimes|integer'
    ];

    protected $payment;

    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    protected function validateCaptcha($attribute, $captchaResponse)
    {
        $app = App::getFacadeRoot();

        if ($app->environment('production') === false)
        {
            return;
        }

        $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $app['request']->ip();

        $noCaptchaSecret = config('app.customer_refund_details.nocaptcha_secret');

        $input = [
            'secret'   => $noCaptchaSecret,
            'response' => $captchaResponse,
            'remoteip' => $clientIpAddress,
        ];

        $captchaQuery = http_build_query($input);

        $url = "https://www.google.com/recaptcha/api/siteverify?". $captchaQuery;

        $response = \Requests::get($url);

        $output = json_decode($response->body);

        if ($output->success !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'captcha' => $captchaResponse
                ]);
        }
    }

    protected function validateStatus($attribute, $value)
    {
        if ($this->entity->getStatus() === Status::PROCESSED)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Status cannot be updated to initiated from processed.',
                'status');
        }

        $validStatus = in_array($value, Status::REFUND_STATUS, true);

        if ($validStatus === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The selected status is invalid.');
        }
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
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                'gateway',
                [
                    'gateway' => $gateway
                ]);
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

    public function validateMarkProcessed()
    {
        $refund = $this->entity;

        //
        // Checking if refund is already processed, as scrooge can call API to mark processed again
        // even if refund has been already updated by some other process (eg. recon)
        //
        if (($refund->isCreated() === false) and ($refund->isProcessed() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_INVALID_STATE_TO_PROCESSED,
                Entity::STATUS,
                [
                    'refund_id' => $refund->getId(),
                    'status'    => $refund->getStatus(),
                ]);
        }
    }

    /**
     * Checks if input status is other than processed.
     *
     * @param array $input
     * @throws Exception\BadRequestException
     */
    public function validateScroogeEditRefund(array $input)
    {
        $refund = $this->entity;

        //
        // For now, edit refund is supporting only status update.
        // Checking if refund is moved to another state apart from Processed, throw exception.
        // For scrooge gateways, refund status can only be updated to `processed`.
        //
        if ($input[Payment\Entity::STATUS] !== Status::PROCESSED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_INVALID_STATE_UPDATE,
                Entity::STATUS,
                [
                    'refund_id' => $refund->getId(),
                    'status'    => $refund->getStatus(),
                ]);
        }
    }

    public function validateScroogeGatewayRefund(Payment\Entity $payment)
    {
        $refund = $this->entity;

        //
        // If it's already marked as processed on API side, there's no reason
        // for us to call the gateway again to make the refund call.
        //
        if ($refund->isProcessed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_ALREADY_PROCESSED,
                Entity::STATUS,
                [
                    'refund_id' => $refund->getId(),
                    'status'    => $refund->getStatus()
                ]);
        }

        //
        // Scrooge should be calling gateway refund only if the refund is still
        // in created state. On refund failure, Scrooge will call the gateway
        // refund again, but in that case refund would still be in created state.
        //
        if ($refund->isCreated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_IN_CREATED,
                Entity::STATUS,
                [
                    'refund_id' => $refund->getId(),
                    'status'    => $refund->getStatus()
                ]);
        }

        $gateway = $refund->getGateway();
        $merchantId = $refund->merchant->getId();

        if (Payment\Gateway::isScroogeGatewayAndMerchant($gateway, $merchantId) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_SCROOGE,
                Entity::STATUS,
                [
                    'refund_id'     => $refund->getId(),
                    'payment_id'    => $payment->getId(),
                    'gateway'       => $gateway,
                ]);
        }
    }
}
