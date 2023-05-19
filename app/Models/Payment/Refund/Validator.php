<?php

namespace RZP\Models\Payment\Refund;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;

class Validator extends Base\Validator
{
    // Refund request related validations should be added here and not in $directRules.
    protected static $createRules = [
        'amount'                => 'sometimes|integer',
        'notes'                 => 'sometimes|notes',
        'receipt'               => 'sometimes|string|max:40',
        'reverse_all'           => 'sometimes|boolean',
        'reversals'             => 'sometimes|array',
        'reversals.*.transfer'  => 'required',
        'reversals.*.amount'    => 'required|integer',
        'reversals.*.notes'     => 'sometimes|notes',
        'speed'                 => 'sometimes|filled|in:optimum,normal',
    ];

    protected static $editStatusRules = [
        Entity::STATUS          => 'required|string|custom',
        Entity::REFERENCE1      => 'sometimes|string|max:255',
    ];

    protected static $editRules = [
        Entity::NOTES => 'sometimes|notes',
    ];

    //
    // Validating only payment_id here. Rest of the validations will be handled by $createRules
    // No other validation should be added here.
    //
    protected static $directRules = [
        'payment_id'    => 'required|public_id',
    ];

    protected static $minAmountCheckRules = [
        'currency'              => 'required|string|size:3',
        'amount'                => 'sometimes|integer|min_amount',
        'reversals.*.amount'    => 'required|integer|min_amount',
    ];

    protected static $retryRules = [
        'bank_account'                      => 'sometimes|array',
        'bank_account.ifsc_code'            => 'required_with:bank_account|alpha_num|size:11',
        'bank_account.account_number'       => 'required_with:bank_account|alpha_num|between:5,22',
        'bank_account.beneficiary_name'     => 'required_with:bank_account|between:4,120|string',
        'vpa'                               => 'sometimes|associative_array',
        'vpa.address'                       => 'required_with:vpa|filled|string',
        'card_transfer'                     => 'sometimes|associative_array',
        'card_transfer.card_id'             => 'required_with:card_transfer|filled|unsigned_id',
    ];

    protected static $retryBulkViaFtaRules = [
        'refund_ids'      => 'required|sequential_array|max:1000',
        'refund_ids.*'    => 'filled|unsigned_id',
        'transfer_method' => 'required|in:source_vpa',
    ];

    protected static $retryScroogeRefundsWithoutVerifyRules = [
        'refund_ids'      => 'required|sequential_array|max:1000',
        'refund_ids.*'    => 'filled|unsigned_id',
    ];

    protected static $createValidators = [
        'paymentStatus',
        'paymentRefundStatus',
        'refundAmount',
        'minRefundAmount',
    ];

    protected static $retryBulkRules = [
        'refund_ids'    => 'required|sequential_array|max:1000',
        'refund_ids.*'  => 'required|filled',
    ];

    protected static $directRetryBulkRules = [
        'refund_ids'    => 'required|sequential_array|max:1000',
        'refund_ids.*'  => 'required|filled',
    ];

    protected static $fetchRefundCreationDataRules = [
        'payment_id' => 'required|public_id',
        'amount'     => 'required|integer|min:0',
    ];

    protected static $markProcessedBulkRules = [
        'refund_ids'       => 'required|sequential_array|max:1000',
        'refund_ids.*'     => 'required|public_id',
        'processed_source' => 'required|string',
    ];

    protected static $customerRefundDetailsRules = [
        'refund_id'         => 'required_without_all:payment_id,reservation_id|public_id',
        'payment_id'        => 'required_without_all:refund_id,reservation_id|public_id',
        'reservation_id'    => 'required_without_all:payment_id,refund_id|string|max:50',
        'mode'              => 'sometimes|in:live,test',
        'captcha'           => 'required|string|custom',
    ];

    protected static $customerRefundsDetailsRules = [
        'refund_id'         => 'required_without_all:payment_id,order_id,id|public_id',
        'payment_id'        => 'required_without_all:refund_id,order_id,id|public_id',
        'order_id'          => 'required_without_all:payment_id,refund_id,id|public_id',
        'id'                => 'required_without_all:payment_id,refund_id,order_id|alpha_num_underscore',
        'mode'              => 'sometimes|in:live,test',
        'captcha'           => 'required|string|custom',
    ];

    protected static $verifyInternalRefundGateways = [
        Payment\Gateway::HDFC,
        Payment\Gateway::AXIS_MIGS,
    ];

    protected static $scroogeGatewayRefundRules = [
        'id'                                        => 'required|unsigned_id',
        'merchant_id'                               => 'required|unsigned_id',
        'payment_id'                                => 'required|unsigned_id',
        'currency'                                  => 'required|string|size:3',
        'gateway'                                   => 'required|string',
        'amount'                                    => 'required|integer|min:0',
        'base_amount'                               => 'required|integer|min:0',
        'method'                                    => 'required|string',
        'payment_amount'                            => 'required|integer|min:0',
        'payment_base_amount'                       => 'required|integer|min:0',
        'payment_created_at'                        => 'required|epoch',
        'attempts'                                  => 'sometimes|integer',
        'status'                                    => 'sometimes|string',
        'fta_data'                                  => 'sometimes|associative_array',
        'created_at'                                => 'sometimes|epoch',
        'last_attempted_at'                         => 'sometimes|epoch',
        'transaction_id'                            => 'sometimes|unsigned_id',
        'fta_data.bank_account'                     => 'sometimes|array',
        'fta_data.bank_account.ifsc_code'           => 'required_with:bank_account|alpha_num|size:11',
        'fta_data.bank_account.account_number'      => 'required_with:bank_account|alpha_num|between:5,22',
        'fta_data.bank_account.beneficiary_name'    => 'required_with:bank_account|between:4,120|string',
        'fta_data.vpa'                              => 'sometimes|associative_array',
        'fta_data.vpa.address'                      => 'required_with:vpa|filled|string',
        'fta_data.card_transfer'                    => 'sometimes|associative_array',
        'fta_data.card_transfer.card_id'            => 'required_with:card_transfer|filled|unsigned_id',
        'is_fta'                                    => 'sometimes|bool',
        'mode_requested'                            => 'sometimes|filled|string|in:IMPS,UPI,NEFT,RTGS,IFT,CT',
    ];

    protected static $createScroogeRefundBulkRules = [
        'refund_ids'    => 'required|sequential_array|max:1000',
        'refund_ids.*'  => 'required|filled',
    ];

    protected static $getFeeRules = [
        'payment_id'    => 'required|public_id',
        'amount'        => 'required|integer|min:0',
    ];

    protected static $scroogeFetchFeeRules = [
        'payment_id'    => 'required|unsigned_id|max:14',
        'amount'        => 'required|integer|min:0',
        'mode'          => 'sometimes|filled|string|in:IMPS,UPI,NEFT,RTGS,IFT,CT',  // instant refunds mode
    ];

    protected static $refundsPaymentUpdateRules = [
        'refunds'               => 'required|array|max:1000',
        'refunds.*.id'          => 'required|unsigned_id',
        'refunds.*.payment_id'  => 'required|unsigned_id',
        'refunds.*.amount'      => 'required|integer',
        'refunds.*.base_amount' => 'required|integer',
    ];

    protected static $refundsTransactionCreateRules = [
        'id'               => 'required|unsigned_id',
        'payment_id'       => 'required|unsigned_id',
        'amount'           => 'required|integer',
        'base_amount'      => 'required|integer',
        'speed_decisioned' => 'required|string|in:instant,optimum,normal',
        'gateway'          => 'required|string',
        'mode'             => 'sometimes|string|in:IMPS,UPI,NEFT,RTGS,IFT,CT',  // instant refunds mode
        'fee'              => 'sometimes|integer',
        'tax'              => 'sometimes|integer',
        'journal_id'       => 'sometimes|unsigned_id',
    ];

    protected static $createReversalRules = [
        'journal_id'               => 'required|unsigned_id|max:14',
        'payment_id'               => 'required|unsigned_id|max:14',
        'refund_id'                => 'required|unsigned_id|max:14',
        'merchant_id'              => 'required|unsigned_id|max:14',
        'speed_decisioned'         => 'required|string',
        'base_amount'              => 'required|integer|min:0',
        'fee'                      => 'required|integer|min:0',
        'tax'                      => 'required|integer|min:0',
        'fee_only_reversal'        => 'required|bool',
        'currency'                 => 'required|string',
        'created_at'               => 'required|epoch',
        'gateway'                  => 'required|string',
    ];

    protected static $refundEmailDataRules = [
        'payment_id'    => 'required|unsigned_id',
        'refund'        => 'required|array',
    ];

    protected static $refundTransactionDataRules = [
        'refund_id'     => 'required|unsigned_id|max:14',
    ];

    protected static $setUnprocessedRefundsConfigRules = [
        'refund_ids'   => 'required|sequential_array|max:5000',
        'refund_ids.*' => 'required|filled|unsigned_id|size:14',
    ];

    protected static $fetchEntitiesV2Rules = [
        'payment_ids'   => 'required|array',
        'payment_ids.*' => 'required|filled|unsigned_id|size:14',
        'entities'      => 'sometimes|array',
        'extra_data'    => 'sometimes|array',
    ];

    protected static $fetchPublicEntitiesRules = [
        'public_entities'                      => 'required|array',
        'public_entities.*.entity_id'          => 'required|unsigned_id|size:14',
        'public_entities.*.entity_type'        => 'required|string',
        'public_entities.*.expand'             => 'sometimes|array',
        'custom_public_entities'               => 'sometimes|array',
        'custom_public_entities.*.entity_id'   => 'required|string|min:1',
        'custom_public_entities.*.entity_type' => 'required|string',
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
                ErrorCode::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT);
        }
    }

    protected function validateMinRefundAmount($input)
    {
        $payment = $this->payment;

        if (isset($input['amount']) === false)
        {
            $input['amount'] = $payment->getAmountUnrefunded();
        }

        $amountCheckInput = [];

        if (empty($input['amount']) === false) {
            $amountCheckInput['amount'] = $input['amount'];
        }

        if (empty($input['reversals']) === false) {
            $amountCheckInput['reversals'] = [
                'amount' => $input['reversals']['amount'],
            ];
        }

        // currency will not be available here. But we need to validate amount based
        // on the existing currency.
        $currency = $payment->getCurrency();

        if (empty($currency) === false)
        {
            $amountCheckInput['currency'] = $currency;
        }

        $this->validateInputValues('min_amount_check', $amountCheckInput);
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

    public function validateUpdateScroogeRefundStatus($input)
    {
        $refund = $this->entity;

        //
        // Checking if refund is already processed, as scrooge can call API to mark processed again
        // even if refund has been already updated by some other process (eg. recon)
        // Refund with initiated status can be marked as processed
        // after FTA recon calls scrooge and scrooge calls back to API.
        //
        if ((($refund->isCreated() === false) and
            ($refund->isProcessed() === false) and
            ($refund->isInitiated() === false)) or
            (isset($input['event']) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_INVALID_EVENT_TO_PROCESS,
                Entity::STATUS,
                [
                    'refund_id' => $refund->getId(),
                    'status'    => $refund->getStatus(),
                    'gateway'   => $refund->getGateway(),
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
        // Checking if refund is moved to another state apart from Processed or Failed, throw exception.
        // For scrooge gateways, refund status can only be updated to `processed` or `failed`(in case of fta).
        //
        if (($input[Entity::STATUS] !== Status::PROCESSED) and ($input[Entity::STATUS] !== Status::FAILED))
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
        // If refund is in initiated state that means fund transfer attempt was done on this refund.
        // Check if any of the attempts is not in failed state. If any of fta is in created or completed,
        // retry should not be possible.
        //
        else if ($refund->isInitiated() === true)
        {
           foreach ($refund->fundTransferAttempts as $fundTransferAttempt)
           {
               if ($fundTransferAttempt->isStatusFailed() === false)
               {
                   throw new Exception\BadRequestException(
                       ErrorCode::BAD_REQUEST_ALL_FTA_NOT_FAILED,
                       Entity::STATUS,
                       [
                           'refund_id'  => $refund->getId(),
                           'status'     => $refund->getStatus(),
                           'fta_status' => $fundTransferAttempt->getStatus()
                       ]);
               }
           }
        }

        //
        // Scrooge should be calling gateway refund only if the refund is still
        // in created state. On refund failure, Scrooge will call the gateway
        // refund again, but in that case refund would still be in created state.
        //
        else if ($refund->isCreated() === false)
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

        if ($refund->isScrooge() === false)
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

    public function validateCustomerRefundFetchDetailsFromMerchantNotes($id)
    {
        $idRegex = '/^.*[0-9]+.*$/';

        $validId = (preg_match($idRegex, $id) === 1);

        if ($validId === false)
        {
            throw new Exception\BadRequestValidationFailureException('The id format is invalid.', 'id');
        }
    }

    public static function validateInstantRefundPricingMethod($method)
    {
        if ((empty($method) === false) and
            (in_array($method, Payment\Method::INSTANT_REFUND_SUPPORTED_METHODS, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Refund method should be ' . implode('/', Payment\Method::INSTANT_REFUND_SUPPORTED_METHODS),
                'method',
                [
                    'method' => $method,
                ]
            );
        }
    }

    public function validateCancelRefundsBatch(array $batch)
    {
        if (in_array($batch[Batch\Entity::STATUS], Batch\Status::REFUND_BATCH_STATUSES_VALID_FOR_CANCEL) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_STATUS_INVALID_FOR_CANCEL,
                $batch[Batch\Entity::STATUS],
                [
                    'batch_id'  => $batch[Batch\Entity::ID],
                    'status'    => $batch[Batch\Entity::STATUS],
                ]
            );
        }
    }
}
