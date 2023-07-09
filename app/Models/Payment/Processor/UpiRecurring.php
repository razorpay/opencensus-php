<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\UpiMandate;
use RZP\Models\Merchant;
use RZP\Services\Reminders;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Entity;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\UpiMetadata;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\PaymentsUpi\Vpa as Vpa;
use RZP\Models\Reminders\ReminderProcessor;
use RZP\Models\UpiMandate\Entity as Mandate;
use RZP\Gateway\Upi\Base\Constants as UpiConstants;
use RZP\Models\Payment\UpiMetadata\Entity as Metadata;
use RZP\Models\Payment\UpiMetadata\InternalStatus as InternalStatus;

trait UpiRecurring
{
    public function processRecurringDebitForUpi(Payment\Entity $payment)
    {
        try
        {
            if($payment->isRecurringTypeAuto())
            {
                $this->validateAutoRecurringForUpiBeforeDebit($payment);
            }
        }
        catch (\Exception $e)
        {
            return;
        }

        $input = [
            'action'        => Payment\Action::DEBIT,
            'gateway'       => $payment->getGateway(),
            'terminal'      => $payment->terminal,
            'payment'       => $payment,
            'merchant'      => $payment->merchant,
        ];

        $this->modifyGatewayInputForUpi($payment, $input);

        $this->mutex->acquireAndRelease('debit_' . $payment->getId(),
            function() use ($input, $payment) {
                try
                {
                    $response = $this->callGatewayFunction(Payment\Action::DEBIT, $input);

                    return $this->processDebitGatewaySuccess($payment, $response);
                }
                catch (Exception\GatewayErrorException $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::INFO,
                        TraceCode::GATEWAY_PAYMENT_ERROR,
                        [
                            'payment_id'    => $input['payment']->getId(),
                            'gateway'       => $input['gateway'],
                            'action'        => $input['action'],
                            'terminal_id'   => $input['terminal']->getId(),
                        ]);

                    $this->processDebitGatewayFailure($payment, $exception);

                    // Since the debit for auto recurring is called by reminder service
                    // we do not need to throw exception back as
                    // 1. This is gateway failure and already handled
                    // 2. Reminder service will retry for 5xx and that's not needed
                    if ($payment->isUpiAutoRecurring() === true)
                    {
                        return;
                    }

                    throw $exception;
                }
            });
    }

    public function processAutoRecurringPreDebitForUpi(Payment\Entity $payment)
    {
        $mandate = $this->repo->upi_mandate->findByTokenId($payment->getTokenId());

        try
        {
            $this->validateAutoRecurringForUpiBeforePreDebit($payment);
        }
        catch (\Exception $e)
        {
            return;
        }

        $input = [
            'action'        => Payment\Action::PRE_DEBIT,
            'gateway'       => $payment->getGateway(),
            'terminal'      => $payment->terminal,
            'payment'       => $payment,
            'merchant'      => $payment->merchant,
        ];

        $this->mutex->acquireAndRelease($payment->getId(),
            function() use ($input, $payment, $mandate) {
                try
                {
                    // Before making gateway call, we will change the status
                    $metadata = $payment->getUpiMetadata();
                    $metadata->setInternalStatus(UpiMetadata\InternalStatus::PRE_DEBIT_INITIATED);
                    (new UpiMetadata\Core)->update($metadata);

                    $this->modifyGatewayInputForUpi($payment, $input);

                    $gatewayResponse = $this->app['gateway']->call(
                        $input['gateway'],
                        $input['action'],
                        $input,
                        $this->mode,
                        $input['terminal']);

                    return $this->processPreDebitGatewaySuccess($payment, $mandate, $gatewayResponse);
                }
                catch (Exception\GatewayErrorException $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::INFO,
                        TraceCode::GATEWAY_PAYMENT_ERROR,
                        [
                            'payment_id'    => $metadata->getPaymentId(),
                            'gateway'       => $input['gateway'],
                            'action'        => $input['action'],
                            'terminal_id'   => $input['terminal']->getId(),
                        ]);

                    return $this->processPreDebitGatewayFailure($payment, $mandate, $exception);
                }
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);
    }

    public function processAutoRecurringAuthorizeForUpi(Payment\Entity $payment)
    {
        $mandate = $this->repo->upi_mandate->findByTokenId($payment->getTokenId());

        $gatewayInput = [
            'selected_terminal_ids' => [$payment->getTerminalId()],
        ];

        $this->modifyGatewayInputForUpi($payment, $gatewayInput);

        return $this->gatewayRelatedProcessing($payment, [], $gatewayInput);
    }

    public function mandateUpdate($customerId, $token, array $input)
    {
        $action = Payment\Action::MANDATE_UPDATE;

        // This will throw bad request validation error
        (new Payment\Validator)->validateInput($action, $input);
        $tokenTerminal = $this->repo->terminal->getById($token[Token\Entity::TERMINAL_ID]);

        if ($tokenTerminal === null)
        {
            throw new Exception\RuntimeException(
                ErrorCode::SERVER_ERROR);
        }

        Customer\Entity::verifyIdAndStripSign($customerId);

        $payment = $this->repo->payment->getByTokenIdAndCustomerId($token['id'], $customerId);

        $gateway = $tokenTerminal->getGateway();

        $input = [
            'terminal'  => $tokenTerminal,
            'gateway'   => $gateway,
            'token'     => $token,
            'payment'   => $payment
        ];

        // Input, GatewayInput and Response are currently same, we are using different variable
        // names as make sure there usage are not mixed, and later they all can be different.
        $gatewayData = $input;


        $gatewayResponse = null;

        $this->mutex->acquireAndRelease($this->getTokenUpdateMutexResource($token),
            function() use ($gatewayData, $action, $gateway, $tokenTerminal) {
                try
                {
                    $gatewayResponse = $this->app['gateway']->call(
                                        $gateway,
                                        $action,
                                        $gatewayData,
                                        $this->mode,
                                        $tokenTerminal);

                    return
                    [
                        'success' => true,
                    ];
                }
                catch (Exception\GatewayErrorException $exception)
                {
                    $this->trace->traceException($exception, Trace::INFO, TraceCode::GATEWAY_MANDATE_UPDATE_ERROR);
                }

                return
                [
                    'success' => false,
                ];
            },
        60,
        ErrorCode::BAD_REQUEST_TOKEN_UPDATION_OPERATION_IN_PROGRESS,
        20,
        1000,
        2000);
    }

    public function mandateCancel($customerId, $upiMandate, $token)
    {
        $action = Payment\Action::MANDATE_CANCEL;

        $tokenTerminal = $this->repo->terminal->getById($token[Token\Entity::TERMINAL_ID]);

        if ($tokenTerminal === null)
        {
            throw new Exception\RuntimeException(
                ErrorCode::SERVER_ERROR);
        }

        if((($token->getEntityType() === \RZP\Constants\Entity::SUBSCRIPTION) and
            ($customerId === "cust_")) === false)
        {
            Customer\Entity::verifyIdAndStripSign($customerId);

            $payment = $this->repo->payment->getByTokenIdAndCustomerId($token['id'], $customerId);
        }
        else
        {
            $payment = $this->repo->payment->fetchInitialPaymentIdForToken($token['id'], $token->getMerchantId());
        }

        $gateway = $tokenTerminal->getGateway();

        $input = [
            'terminal'    => $tokenTerminal,
            'gateway'     => $gateway,
            'token'       => $token,
            'payment'     => $payment,
            'merchant'    => $this->merchant,
            'upi_mandate' => $upiMandate,
            'upi'         => [
                'expiry_time' => 10,
            ]
        ];

        // Input, GatewayInput and Response are currently same, we are using different variable
        // names as make sure there usage are not mixed, and later they all can be different.
        $gatewayData = $input;

        $gatewayResponse = null;

        $this->mutex->acquireAndRelease($this->getMandateUpdateMutexResource($upiMandate),
            function() use ($gatewayData, $action, $gateway, $tokenTerminal, $upiMandate) {
                try
                {
                    $gatewayResponse = $this->app['gateway']->call(
                        $gateway,
                        $action,
                        $gatewayData,
                        $this->mode,
                        $tokenTerminal);

                    $upiMandate->setStatus(UpiMandate\Status::REVOKED);

                    (new UpiMandate\Core)->update($upiMandate);

                    (new Token\Core)->cancelTokenEvent($upiMandate->getTokenId(), $upiMandate->getCustomerId());

                    return
                        [
                            'success' => true,
                        ];
                }
                catch (Exception\GatewayErrorException $exception)
                {
                    throw $exception;
                }
            },
            60,
            ErrorCode::BAD_REQUEST_TOKEN_UPDATION_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);
    }

    public function mandatePause($input, $upiMandate)
    {
        $upiMandate->setStatus(UpiMandate\Status::PAUSED);

        (new UpiMandate\Core)->update($upiMandate);

        (new Token\Core)->pauseTokenEvent($upiMandate->getTokenId(), $upiMandate->getCustomerId());

        return ['success' => true];
    }

    public function mandateResume($input, $upiMandate)
    {
        $upiMandate->setStatus(UpiMandate\Status::CONFIRMED);

        (new UpiMandate\Core)->update($upiMandate);

        (new Token\Core)->resumeTokenEvent($upiMandate->getTokenId(), $upiMandate->getCustomerId());

        return ['success' => true];
    }

    public function mandateCancelViaCallback($input, $upiMandate)
    {
        $upiMandate->setStatus(UpiMandate\Status::REVOKED);

        $this->repo->saveOrFail($upiMandate);

        (new Token\Core)->cancelTokenEvent($upiMandate->getTokenId(), $upiMandate->getCustomerId());

        return ['success' => true];
    }

    protected function getTokenUpdateMutexResource(Token\Entity $token): string
    {
        return 'token_update_' . $token->getId();
    }

    protected function getMandateUpdateMutexResource(UpiMandate\Entity $upiMandate): string
    {
        return 'mandate_update_' . $upiMandate->getId();
    }

    public function mandateUpdateCallback(Payment\Entity $payment, $input)
    {
        $token = $this->repo->token->findByIdAndMerchant($payment->getTokenId(), $this->merchant);

        $tokenTerminal = $this->repo->terminal->getById($token[Token\Entity::TERMINAL_ID]);

        $action = Payment\Action::CALLBACK;

        $gateway = $tokenTerminal->getGateway();

        $input['token'] = $token;

        $gatewayData['gateway'] = $input;

        $gatewayData['payment'] = $payment;

        $gatewayData['terminal'] = $tokenTerminal;

        try
        {
            $gatewayResponse = $this->app['gateway']->call
            ($gateway, $action, $gatewayData, $this->mode, $tokenTerminal);

            $token[Token\Entity::START_TIME] = $gatewayResponse['start_time'];

            $token[Token\Entity::MAX_AMOUNT] = $gatewayResponse['amount'];

            $this->repo->saveOrFail($token);

            return
            [
                'success'  => true,
            ];
        }

        catch (Exception\GatewayErrorException $exception)
        {
            $this->trace->traceException($exception, Trace::INFO, TraceCode::GATEWAY_MANDATE_UPDATE_ERROR);
        }

        return
        [
            'success' => false,
        ];
    }

    protected function validateRecurringForUpi(
        Payment\Entity $payment, Token\Entity $token, array $input)
    {
        //
        // The below two validations are being done here and not as part of
        // Validator Rules because after the payment build, we give the
        // control to frontend to take missing attributes from the customer.
        // So, as part of validation, we use `sometimes` for these fields.
        // Ideally, this should never happen since we anyway ensure that
        // we collect the missing attributes from the customer before
        // proceeding further.
        //
        // We need bank_account details and auth_type only for first recurring payments.
        // For the second recurring payments, we don't require auth type and bank_account
        // details would be present in the token itself.
        //
        // ISSUE: Since we are doing the validation here (after the token is created),
        // it's possible that the tokens are created without the required bank account details.
        //
        if ($payment->isRecurringTypeInitial() === true)
        {
            $this->validateInitialRecurringForUpi($payment, $input);
        }
        else if ($payment->isRecurringTypeAuto() === true)
        {
            $this->validateAutoRecurringForUpi($payment, $input, $token);
        }
        else
        {
            throw new Exception\LogicException(
                'Payment should either be initial recurring or auto recurring.',
                null,
                [
                    'payment'        => $payment->getId(),
                    'recurring_type' => $payment->getRecurringType(),
                    'auth_type'      => $payment->getAuthType()
                ]);
        }

        //
        // TODO: This is broken still. We should not be accepting any token
        // in private auth also for first recurring. But, in private auth,
        // it could be second recurring also, where we accept a token.
        //
        if (($this->ba->isPublicAuth() === true) and
            (empty($input[Payment\Entity::TOKEN]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMANDATE_TOKEN_PASSED_IN_FIRST_RECURRING,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                    'token'   => $token->toArray(),
                ]);
        }

        // Customer fee bearer is not allowed on netbanking recurring
        if ($payment->merchant->isFeeBearerCustomer() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment failed. Please contact the merchant for further assistance.',
                null,
                [
                    'payment_id' => $payment->getId()
                ]);
        }

        $this->validateTokenRecurringStatus($token, $payment);

        $this->validateTokenMaxAmount($token, $payment);

        $this->validateTokenExpiredAt($token);
    }

    protected function validateInitialRecurringForUpi(Payment\Entity $payment, array $input)
    {
        $order = $payment->order;

        if ($payment->getAmount() !== $order->getAmount() and
            ($payment->getOffer() === null and $payment->hasSubscription() === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The initial payment amount must be equal to order amount for upi recurring',
                Payment\Entity::AMOUNT,
                [
                    'payment_amount'    => $payment->getAmount(),
                    'payment_id'        => $payment->getId(),
                    'method'            => $payment->getMethod(),
                    'recurring_type'    => $payment->getRecurringType(),
                    'order_amount'      => $order->getAmount(),
                ]);
        }
    }

    protected function validateAutoRecurringForUpi(Payment\Entity $payment, array $input, Token\Entity $token)
    {
        $upiMandate = $token->getUpiMandate();

        $lastSuccessDebitTimeStamp = $upiMandate['gateway_data']['lsd'] ?? null;

        // validation for fixed frequencies
        if(($upiMandate['frequency'] !== UpiMandate\Frequency::AS_PRESENTED) and
            ($upiMandate->getFrequency() !== UpiMandate\Frequency::DAILY) and
            ($lastSuccessDebitTimeStamp !== null))
        {
            $sequenceNumber = new UpiMandate\SequenceNumber($lastSuccessDebitTimeStamp, Carbon::now()->getTimestamp());
            $recurType = $upiMandate['recurring_type'];
            $recurVal = $upiMandate['recurring_value'];
            $frequency = $upiMandate['frequency'];

            if($sequenceNumber->isValidExecutionDate($frequency) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Customer has been already debited for the current cycle. Initiate the debit in next cycle to charge the customer',
                    null,
                    []);
            }

            if($sequenceNumber->isValidCycle($recurType, $recurVal, $frequency) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Debit not allowed at this time. Debit needs to be charged within the cycle & 26 hours before the last date',
                    null,
                    []);
            }
        }

        // if mandate expiry is withing 26 hr we'll not allow to create subsequent payment.
        if ($upiMandate !== null)
        {
            $mandateExpiry = $upiMandate['end_time'];
            $currentTime = Carbon::now()->getTimestamp();
            $diffInHours = floor(($mandateExpiry - $currentTime)/3600);

            if($diffInHours <= 26)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'You cannot initiate subsequent payments within 26 hours of expiration of the mandate',
                    null,
                    []);
            }
        }
    }

    protected function validateAutoRecurringForUpiBeforePreDebit(Entity $payment)
    {
        try
        {
            $token = $this->repo->token->find($payment->getTokenId());

            if(($token === null) or
               ($token->getRecurringStatus() !== Token\RecurringStatus::CONFIRMED))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
                    null,
                    [
                        'payment_id'      => $payment->getId(),
                        'token_id'        => $token ? $token->getId() : null,
                        'recurring_status'=> $token ? $token->getRecurringStatus() : null,
                        'merchant_id'     => $payment->getMerchantId(),
                        'step'             => 'pre-debit'
                    ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_RECURRING_UNCONFIRMED_TOKEN_IN_AUTO_DEBIT);

            $this->lockForUpdateAndReload($payment);

            $metadata = $payment->getUpiMetadata();
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::PRE_DEBIT_FAILED);
            $metadata->setRemindAt(null);
            (new UpiMetadata\Core)->update($metadata);

            $this->updatePaymentAuthFailed($e);

            throw $e;
        }
    }

    protected function validateAutoRecurringForUpiBeforeDebit(Entity $payment)
    {
        try
        {
            $token = $this->repo->token->find($payment->getTokenId());

            if(($token === null) or
               ($token->getRecurringStatus() !== Token\RecurringStatus::CONFIRMED))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
                    null,
                    [
                        'payment_id'      => $payment->getId(),
                        'token_id'        => $token ? $token->getId() : null,
                        'recurring_status'=> $token ? $token->getRecurringStatus() : null,
                        'merchant_id'     => $payment->getMerchantId(),
                        'step'             => 'debit'
                    ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_RECURRING_UNCONFIRMED_TOKEN_IN_AUTO_DEBIT);

            $this->lockForUpdateAndReload($payment);

            $metadata = $payment->getUpiMetadata();
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::FAILED);
            $metadata->setRemindAt(null);
            (new UpiMetadata\Core)->update($metadata);

            $this->updatePaymentAuthFailed($e);

            throw $e;
        }
    }
    /**
     * Only called from Payment::runPaymentMethodRelatedPreProcessing, when even the payment is not commited to DB
     */
    protected function modifyRecurringForUpiIfApplicable(Entity $payment, array & $input, array & $gatewayInput)
    {
        // Do nothing for other payments
        if ($payment->isUpiRecurring() === false)
        {
            return;
        }

        // We need to disable verify for auto recurring payments as these will be left in
        // created state for at least 24 hours, Authorize call on this payment will enable verify again
        if (($payment->isUpiAutoRecurring() === true) and
            ($payment->exists === false))
        {
            $payment->setNonVerifiable();
        }

        $metadata = $this->getUpiMetadataForPayment($payment);

        if ($metadata->exists === false)
        {
            if ($payment->isUpiAutoRecurring() === true)
            {
                $metadata->setMode(UpiMetadata\Mode::AUTO);
                $metadata->setType(UpiMetadata\Type::RECURRING);

                // Adding 30 seconds as buffer
                $metadata->setRemindAt($metadata->freshTimestamp() + 30);
                $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_PENDING_FOR_PRE_DEBIT);
            }
            // Else it is initial recurring
            else
            {
                $metadata->setMode(UpiMetadata\Mode::INITIAL);
                $metadata->setType(UpiMetadata\Type::RECURRING);

                // reminder not needed for initial recurring
                $metadata->setRemindAt(null);
                $metadata->setInternalStatus(UpiMetadata\InternalStatus::PENDING_FOR_AUTHENTICATE);
            }
        }

        // Now for gateway input part, we need to attack extra fields to the upi block
        $gatewayInput['upi'] = $metadata->toArray();
    }

    /**
     * Only for auto recurring payment we can skip the authorize flow,
     * Authenticate on Initial needs to hit authorize for terminal selection
     * First Debit on Initial needs to hit authorize for
     */
    protected function shouldHitAuthorizeOnRecurringForUpi(Entity $payment, array $gatewayInput)
    {
        if ($payment->isUpiAutoRecurring() === false)
        {
            return true;
        }

        $metadata = $this->getUpiMetadataForPayment($payment);

        if ($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE))
        {
            return true;
        }

        return false;
    }

    // This method will be called where ever Debit request is needed to gateway
    protected function shouldHitDebitOnRecurringForUpi(Entity $payment)
    {
        $metadata = $payment->getUpiMetadata();

        // Only if the Upi Metadata status is marked PENDING_FOR_AUTHORIZE we can hit the debit on gateway
        // Note: updateRecurringEntitiesForUpiIfApplicable method will mark it only when mandate is confirmed
        if ($metadata->isInternalStatus(UpiMetadata\InternalStatus::PENDING_FOR_AUTHORIZE) === true)
        {
            return true;
        }

        return false;
    }

    /**
     * Called from updateAndNotifyPaymentAuthorized which is called after
     * Payment::authorize
     * Payment::processPaymentCallback
     *
     * @param Entity $payment
     * @param array $data
     * @return bool
     */
    protected function shouldSkipAuthorizeOnRecurringForUpi(Entity $payment, array $data): bool
    {
        // Only for UPI Recurring payments
        if ($payment->isUpiRecurring() === false)
        {
            return false;
        }

        $metadata       = $this->getUpiMetadataForPayment($payment);
        $internalStatus = $data['upi']['internal_status'] ?? null;

        $this->trace->info(
            TraceCode::UPI_RECURRING_METADATA_STATUS_DURING_AUTHORIZE_FLOW,
            [
                'metadata_entity_status'  => $metadata->getInternalStatus() ??  null,
                'gateway_internal_status' => $internalStatus ?? null,
                'payment_id'              => $payment->getId(),
                'payment_status'          => $payment->getStatus(),
                'payment_recurring_status'=> $payment->getRecurringType(),
            ]
        );

        $token = $payment->getGlobalOrLocalTokenEntity();

        $upiMandate = $token->upiMandate;

        if($payment->isRecurringTypeInitial() === true)
        {
            if ($upiMandate === null)
            {
                $upiMandate = $this->repo->upi_mandate->findByOrderId($payment->order->getId());

                $upiMandateLinkedToken = $this->repo->token->findByIdAndMerchantId($upiMandate->getTokenId(), $token->getMerchantId());

                if($upiMandateLinkedToken->getRecurringStatus() !== Token\RecurringStatus::CONFIRMED)
                {
                    $upiMandate->setTokenId($payment->localToken->getId());

                    $token->upiMandate()->save($upiMandate);

                    $token->refresh();

                    $this->repo->saveOrFail($upiMandate);

                    $this->trace->info(
                        TraceCode::UPI_RECURRING_RELINK_UPIMANDATE_TOKEN,
                        [
                            'payment_id'    => $payment->getId(),
                            'token_id'      => $token->getId(),
                        ]
                    );
                }
            }
        }

        if(($upiMandate->getFrequency() !== UpiMandate\Frequency::AS_PRESENTED) and
            ($upiMandate->getFrequency() !== UpiMandate\Frequency::DAILY) and
            ($internalStatus === UpiMetadata\InternalStatus::AUTHORIZED))
        {
            $upiMandateGatewayData = $upiMandate->getGatewayData();

            $upiMandateGatewayData[UpiConstants::LAST_SUCCESSFUL_DEBIT] = Carbon::now()->getTimestamp();

            $upiMandate->setGatewayData($upiMandateGatewayData);

            $this->repo->saveOrFail($upiMandate);
        }

        // For Auto Recurring
        if ($payment->isUpiAutoRecurring() === true)
        {
            // When the metadata status is ReminderInProgressForAuthorized or AuthorizeInitiated
            // Then we will check for internal status if any sent from gateway
            // for recon flow failed to authorized flow, we will have internal status as failed as well, so that time,
            // we will use verify to get internal status as authorized and should return false
            if (($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE)) or
                ($metadata->isInternalStatus(UpiMetadata\InternalStatus::AUTHORIZE_INITIATED)) or
                ($metadata->isInternalStatus(UpiMetadata\InternalStatus::FAILED)))
            {
                // If gateway is explicitly telling that the payment is authorized at gateways end
                // We will not skip authorize for those cases
                if ($internalStatus === UpiMetadata\InternalStatus::AUTHORIZED)
                {
                    return false;
                }
            }

            return true;
        }
        // For Initial Recurring

        // If gateway suggests that the payment is authorized, we do not need to skip the authorization
        if ($internalStatus === UpiMetadata\InternalStatus::AUTHORIZED)
        {
            return false;
        }

        // Gateway is not marking the upi initial payment as authorized, thus we can not authorize the payment
        return true;
    }

    /**
     * This method is called where we have not hit the gateway for authorize
     * That is when merchant makes the request for auto recurring payments.
     * Note: If we decide to hit the gateway for performance or optimisation
     *       We need to use self::updateRecurringEntitiesForUpiIfApplicable
     */
    protected function processRecurringCreatedForUpi(Entity $payment, array $data)
    {
        if ($payment->isUpiAutoRecurring() === true)
        {
            $this->updateAutoRecurringEntitiesForUpi($payment, $data);

            $data = ['razorpay_payment_id' => $payment->getPublicId()];

            if (($payment->hasOrder() === true) and
                ($this->app['basicauth']->isProxyOrPrivilegeAuth() === false) and
                ($this->app->runningInQueue() === false))
            {
                $this->fillReturnDataWithOrder($payment, $data);
            }

            return $data;
        }

        throw new Exception\LogicException('Should not be called for any payment other than Upi Auto Recurring');
    }

    // This function will be called in two cases where Authorize is called.
    // In first case when merchant has sent a request where we are going to create an auto recurring payment,
    // the flow goes like this.
    // 1. First we will have payment created
    // 2. We will make a RS call and get the reminder_id
    // In Second case where RS calls for authorization and a callback is expected from gateway
    protected function updateAutoRecurringEntitiesForUpi(Entity $payment, array $data)
    {
        $metadata = $payment->getUpiMetadata();

        // First case, where reminder is supposed to be sent for payment
        if ($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_PENDING_FOR_PRE_DEBIT))
        {
            // Make actual call to create a reminder
            $reminderId = $this->setUpiAutoRecurringReminder($metadata);

            if (empty($reminderId) === false)
            {
                $metadata->setReminderId($reminderId);
                $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_PRE_DEBIT);

                (new UpiMetadata\Core)->update($metadata);
            }
            // If reminder fails, the payment is already in pending state
        }
        // Second case where the response is coming from gateway, Gateway might also send Authorize initiated
        // failed in case for recon flow
        else if (($metadata->isInternalStatus(InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE) === true) or
                 ($metadata->isInternalStatus(InternalStatus::AUTHORIZE_INITIATED)) or
                 (($metadata->isInternalStatus(InternalStatus::FAILED)) and
                  (isset($data['upi'][Metadata::INTERNAL_STATUS]) === true) and
                  ($data['upi'][Metadata::INTERNAL_STATUS] === InternalStatus::AUTHORIZED)))
        {
            $upiEdit = array_only($data['upi'], $metadata->getFillable());

            $internalStatus = $data['upi'][Metadata::INTERNAL_STATUS];

            $this->trace->info(
                TraceCode::UPI_RECURRING_METADATA_STATUS_DURING_AUTHORIZE_FLOW,
                [
                    'metadata_entity_status'  => $metadata->getInternalStatus() ??  null,
                    'gateway_internal_status' => $internalStatus ?? null,
                    'payment_id'              => $payment->getId(),
                    'payment_status'          => $payment->getStatus(),
                    'payment_recurring_status'=> $payment->getRecurringType(),
                ]
            );

            $metadata->edit($upiEdit);

            // If gateway suggesting that internal status is authorized
            if ($internalStatus === InternalStatus::AUTHORIZED)
            {
                $metadata->setInternalStatus(InternalStatus::AUTHORIZED);
            }
            else
            {
                $metadata->setInternalStatus(InternalStatus::AUTHORIZE_INITIATED);
            }

            $metadata->setRemindAt(null);

            (new UpiMetadata\Core)->update($metadata);

            // Now since we are expecting a callback from gateway, we can enable the verify for payment
            // But since it is auto recurring payment and neither customer not merchant is blocked on this
            // We can later increase the verify for the payment.
            if ($payment->isCreated() === true)
            {
                $payment->setVerifyAt(Carbon::now()->addMinutes(2)->getTimestamp());

                $this->repo->saveOrFail($payment);
            }
        }
        else
        {
            $this->trace->critical(
                TraceCode::PAYMENT_RECURRING_INVALID_STATUS,
                [
                    'method'            => __FUNCTION__,
                    'payment_id'        => $payment->getId(),
                    'gateway'           => $payment->getGateway(),
                    'internal_status'   => $metadata->getInternalStatus(),
                    'data'              => $data,
                ]);
        }
    }

    protected function processPreDebitGatewaySuccess(Entity $payment, Mandate $mandate, array $response)
    {
        $metadata = $payment->getUpiMetadata();

        $upiEdit = array_only($response['upi'], $metadata->getFillable());
        $metadata->edit($upiEdit);

        // For certain frequencies, gateways might ask to skip the notification
        if ($metadata->getRemindAt() === null)
        {
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE);
            (new UpiMetadata\Core)->update($metadata);

            return true;
        }

        $reminderId = $this->setUpiAutoRecurringReminder($metadata);

        if (empty($reminderId) === false)
        {
            $metadata->setReminderId($reminderId);
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE);
        }
        else
        {
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_PENDING_FOR_AUTHORIZE);
        }

        (new UpiMetadata\Core)->update($metadata);

        return true;
    }

    protected function processPreDebitGatewayFailure(
        Entity $payment,
        Mandate $mandate,
        Exception\GatewayErrorException $exception)
    {
        $response = $exception->getData();

        $metadata = $payment->getUpiMetadata();

        $upiEdit = array_only($response['upi'], $metadata->getFillable());
        $metadata->edit($upiEdit);

        // For exception, where retries are exhausted gateway will send remind at null
        if ($metadata->getRemindAt() === null)
        {
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::PRE_DEBIT_FAILED);
            (new UpiMetadata\Core)->update($metadata);

            $this->payment = $payment;

            // check if gateway status is revok or pause then revoke mandate and token
            if(((new \RZP\Gateway\Upi\Icici\Gateway())->checkGatewayStatusAndUpdateEntity
              ($exception->getGatewayErrorCodeAndDesc()[0], $this->payment->getMerchantId(), $mandate)) === true)
            {
                $this->trace->info(
                    TraceCode::UPI_RECURRING_UPDATE_TOKEN_STATUS,
                    [
                        'mandate'                 => $mandate,
                        'payment_id'              => $payment->getId(),
                        'payment_status'          => $payment->getStatus(),
                        'payment_recurring_type'  => $payment->getRecurringType(),
                    ]
                );

                $input['upi_mandate'] = $mandate;

                $this->processMandateAndTokenStatus($input, Gateway::UPI_ICICI);
            }

            $this->updatePaymentAuthFailed($exception);

            return true;
        }

        $reminderId = $this->setUpiAutoRecurringReminder($metadata);

        if (empty($reminderId) === false)
        {
            $metadata->setReminderId($reminderId);
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_PRE_DEBIT);
        }
        else
        {
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_PENDING_FOR_PRE_DEBIT);
        }

        (new UpiMetadata\Core)->update($metadata);

        return true;
    }

    protected function processMandateAndTokenStatus($input, $gatewayDriver)
    {
        [$id, $mode] = $this->app['repo']->upi_mandate->determineIdAndLiveOrTestModeForEntityWithUMN($input['upi_mandate']['umn']);

        if ($mode === null)
        {
            throw new Exception\LogicException(
                'UMN not found in either database',
                null,
                [
                    'gateway'    => $gatewayDriver,
                    'umn'        => $input['umn'],
                ]);
        }
        else
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);

            $id = \RZP\Models\UpiMandate\Entity::getSignedId($id);

            switch($input['upi_mandate']['status'])
            {
                case 'pause':
                    return (new Payment\Service)->mandatePauseCallback($id, $input, $gatewayDriver);
                case 'resume':
                    return (new Payment\Service)->mandateResumeCallback($id, $input, $gatewayDriver);
                case 'revoke':
                    return (new Payment\Service)->mandateCancelCallback($id, $input, $gatewayDriver);
            }
        }
    }


    protected function processDebitGatewaySuccess(Entity $payment, array $response, bool $wasFailed = false)
    {
        // Here the response is mostly that the debit is initiated at gateway and now
        // we will need to wait for callback
        if ($this->shouldSkipAuthorizeOnRecurringForUpi($payment, $response) === true)
        {
            // Here we can mark the internal_status as AUTHORIZE INITIATED
            $response['upi'][Metadata::INTERNAL_STATUS] = UpiMetadata\InternalStatus::AUTHORIZE_INITIATED;

            $this->updateRecurringEntitiesForUpiIfApplicable($payment, $response);

            return true;
        }

        // Gateway Success is suggesting to mark payment authorized, it can happen for these reasons.
        // 1. For S2S request, Gateway is telling that payment is already authorized at gateway and
        //    there is no need to wait for callback.
        // 2. For callback, Gateway has verified the callback request and found payment to be successful.

        $this->payment = $payment;

        $this->updateAndNotifyPaymentAuthorized($response, $wasFailed);

        $this->postPaymentAuthorizeOfferProcessing($payment);

        $this->autoCapturePaymentIfApplicable($payment);

        return true;
    }

    protected function processDebitGatewayFailure(
        Entity $payment,
        Exception\GatewayErrorException $exception)
    {
        $response = $exception->getData();

        $metadata = $payment->getUpiMetadata();

        $upiEdit = array_only($response['upi'], $metadata->getFillable());
        $metadata->edit($upiEdit);

        // For exception, where retries are exhausted gateway will send remind at null
        if ($metadata->getRemindAt() === null)
        {
            $this->repo->transaction(
                function() use ($payment, $metadata, $exception)
                {
                    $this->payment = $payment;

                    $this->lockForUpdateAndReload($payment);

                    $metadata->setInternalStatus(UpiMetadata\InternalStatus::FAILED);
                    (new UpiMetadata\Core)->update($metadata);

                    $this->updatePaymentAuthFailed($exception);
                });

            return true;
        }

        $reminderId = $this->setUpiAutoRecurringReminder($metadata);

        if (empty($reminderId) === false)
        {
            $metadata->setReminderId($reminderId);
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE);
        }
        else
        {
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_PENDING_FOR_AUTHORIZE);
        }

        (new UpiMetadata\Core)->update($metadata);

        return true;
    }

    protected function updateUpiMetadataOnAuthorized(Entity $payment, array $data)
    {
        // If not UPI, nothing to be done
        if ($payment->isUpi() === false)
        {
            return;
        }

        if (isset($data['upi']) === false)
        {
            if ($payment->isUpiAutoRecurring() === true)
            {
                throw new Exception\LogicException('UPI auto recurring must have upi block in response');
            }
            else
            {
                return;
            }
        }

        // now we can simply update the upi block
        $metadata = $payment->getUpiMetadata();

        if (($metadata instanceof UpiMetadata\Entity) === false)
        {
            $this->trace->critical(
                TraceCode::PAYMENT_UPI_METADATA_NOT_FOUND,
                [
                    'payment_id' => $payment->getId(),
                ]);

            return;
        }

        $upiEdit = array_only($data['upi'], $metadata->getFillable());

        $metadata->edit($upiEdit);
        $metadata->setInternalStatus(UpiMetadata\InternalStatus::AUTHORIZED);
        $metadata->setRemindAt(null);

        $this->repo->saveOrFail($metadata);
    }

    protected function setUpiAutoRecurringReminder(UpiMetadata\Entity $metadata)
    {
        $reminderId = $metadata->getReminderId();

        $reminderData = [
            'remind_at' => $metadata->getRemindAt(),
        ];

        $env = $this->app['env'];

        if (($env === 'func') or
            ($env === 'automation') or
            ($env === 'bvt') or
            ($env === 'availability'))
        {
            $reminderData = [
                'remind_at' => Carbon::now()->getTimestamp()+5,
            ];
        }

        $namespace  = ReminderProcessor::UPI_AUTO_RECURRING;
        $paymentId  = $metadata->getPaymentId();
        $merchantId = Merchant\Account::SHARED_ACCOUNT;
        $url = sprintf('reminders/send/%s/payment/%s/%s', $this->mode, $namespace, $paymentId);

        $request = [
            'namespace'     => $namespace,
            'entity_id'     => $paymentId,
            'entity_type'   => UpiMetadata\Entity::PAYMENT,
            'reminder_data' => $reminderData,
            'callback_url'  => $url,
        ];

        $response = [];

        try
        {
            // Reminder was never created
            if (empty($reminderId) === false)
            {
                $response = $this->app['reminders']->updateReminder($request, $reminderId, $merchantId);
            }
            else
            {
                $response = $this->app['reminders']->createReminder($request, $merchantId);
            }
        }
        catch (\Throwable $e)
        {
            // We will have fallback for reminder failures, thus no need to throw exception
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REMINDERS_RESPONSE,
                [
                    'request'           => $request,
                    'merchant_id'       => $merchantId,
                ]);
        }

        return array_get($response, Entity::ID);
    }

    // Not used
    protected function shouldDebitRecurringPaymentForUpi(array $input, array $data)
    {
        return (($this->isFirstUpiRecurringPayment($input['payment']) === true) and
                ($this->isMandateCreateCallback($data) === true));
    }

    // Not used
    protected function isFirstUpiRecurringPayment($payment): bool
    {
        return ($payment['method'] === 'upi' and $payment['recurring_type'] === 'initial');
    }

    /**
     * This method updates the gateway input for all gateway calls except for Authorize
     * Because there is a different method which does extra processing on these entities
     * Payment::modifyRecurringForUpiIfApplicable will update the metadata before setting
     */
    protected function modifyGatewayInputForUpi(Entity $payment, array & $input)
    {
        if ($payment->isUpiOtm() === true)
        {
            $input['upi'] = $payment->getUpiMetadata()->toArray();
        }

        //Adding data for upi mandate.
        if ($payment->isUpiRecurring() === true)
        {
            if ($payment->isRecurringTypeAuto())
            {
                // UPI Mandate is linked with Payment Local Token and not always with payment order
                $upiMandate = $payment->getGlobalOrLocalTokenEntity()->upiMandate;
            }
            else
            {
                // TODO: Need to check and fix this to a central approach
                $upiMandate = $this->repo->upi_mandate->findByOrderId($payment['order_id']);
            }

            $input['upi_mandate'] = $upiMandate->toArray();
            $input['upi']         = $payment->getUpiMetadata()->toArray();
        }
    }

    protected function isMandateCreateCallback(array $data)
    {
        if (isset($data['mandate']) === true)
        {
            return true;
        }

        return false;
    }

    protected function updateRecurringRequestForUpiIfApplicable(Entity $payment, & $request)
    {
        // Request could be anything bool, null or array
        if (is_array($request) === false)
        {
            return;
        }

        if ($payment->isUpiRecurring() === false)
        {
            return;
        }

        $data = array_only($request, 'data');

        $this->updateRecurringEntitiesForUpiIfApplicable($payment, $request);

        // Now the request will only have data block
        $request = $data;
    }

    /**
     * Called from
     * @param Entity $payment
     * @param array $data
     */
    protected function updateRecurringEntitiesForUpiIfApplicable(Entity $payment, array $data, bool $wasFailed = false)
    {
        if ($payment->isUpiRecurring() === false)
        {
            return;
        }

        if ($payment->isUpiAutoRecurring() === true)
        {
            $this->updateAutoRecurringEntitiesForUpi($payment, $data);

            return;
        }

        $token = $payment->getGlobalOrLocalTokenEntity();

        // As this is made sure that the payment will be created only for local token
        $upiMandate = $token->upiMandate;
        $prevStatus = $upiMandate->getStatus();
        $confirmed  = false;
        $tokenRejected   = false;

        $attributes = $data['upi_mandate'];

        $status = array_pull($attributes, 'status');

        $metadata = $payment->getUpiMetadata();

        $vpa = $data['upi'][UpiMetadata\Entity::VPA] ?? null;

        $upiMandate->edit($attributes);

        if ($status !== null)
        {
            $upiMandate->setStatus($status);

            // Only if when the mandate was in created status and now it's getting to confirmed
            if (($prevStatus === UpiMandate\Status::CREATED) and
                ($status === UpiMandate\Status::CONFIRMED))
            {
                $upiMandate->setVpa($vpa);
                $upiMandate->setLateConfirmed($wasFailed);
                $confirmed = true;
            }
            // When the mandate was in created status and now it was reject by user
            else if (($prevStatus === UpiMandate\Status::CREATED) and
                     ($status === UpiMandate\Status::REJECTED))
            {
                $tokenRejected = true;
            }

            $flow = $metadata->getFlow();

            $this->updateUpiMandateFlowIfApplicable($upiMandate, $flow);
        }

        (new UpiMandate\Core)->update($upiMandate);

        $internalStatus = $data['upi'][UpiMetadata\Entity::INTERNAL_STATUS] ?? null;
        $newStatus      = null;
        $tokenInitiated = false;

        // Mandate just got recently confirmed from created state
        // this is rather critical check which is why we are not directly relying to gateway status
        if ($confirmed === true)
        {
            $newStatus = UpiMetadata\InternalStatus::PENDING_FOR_AUTHORIZE;
        }
        // This is the case where request is send to customer and we are waiting for callback of Mandate Create
        else if ($internalStatus === UpiMetadata\InternalStatus::AUTHENTICATE_INITIATED)
        {
            $newStatus = UpiMetadata\InternalStatus::AUTHENTICATE_INITIATED;
            $tokenInitiated = true;
        }
        else if ($internalStatus === UpiMetadata\InternalStatus::AUTHORIZE_INITIATED)
        {
            $newStatus = UpiMetadata\InternalStatus::AUTHORIZE_INITIATED;
        }
        else if ($internalStatus === UpiMetadata\InternalStatus::AUTHORIZED)
        {
            $newStatus = UpiMetadata\InternalStatus::AUTHORIZED;
        }
        else if ($internalStatus === UpiMetadata\InternalStatus::FAILED)
        {
            $newStatus = UpiMetadata\InternalStatus::FAILED;
        }

        $vpaId = $this->updateMetadataAndCreateVpaEntityIfApplicable($metadata, $token, $vpa);

        if ($tokenInitiated === true)
        {
            (new Token\Core)->updateTokenForUpi($token, [
                Token\Entity::VPA_ID            => $vpaId,
                Token\Entity::RECURRING_STATUS  => Token\RecurringStatus::INITIATED,
            ]);
        }
        // This is the case when mandate create callback is rejected by user
        else if ($tokenRejected === true)
        {
            (new Token\Core)->updateTokenForUpi($token, [
                Token\Entity::VPA_ID                   => $vpaId,
                Token\Entity::RECURRING_STATUS         => Token\RecurringStatus::REJECTED,
                Token\Entity::RECURRING_FAILURE_REASON => $payment->getErrorDescription(),
            ]);
        }
        else if ((is_null($token->getVpaId()) === true) and
                 (is_null($vpaId) === false))
        {
            (new Token\Core)->updateTokenForUpi($token, [
                Token\Entity::VPA_ID            => $vpaId,
                Token\Entity::RECURRING_STATUS  => $token->getRecurringStatus(),
            ]);
        }

        if (is_null($newStatus) === false)
        {
            $metadata->setInternalStatus($newStatus);
        }

        // Since the internal_status and flow in metadata are updated separately,
        // so we'll make the DB call only in case some value was updated
        if ($metadata->isDirty() === true)
        {
            (new UpiMetadata\Core)->update($metadata);
        }
    }

    /**
     * If the flow is Intent:
     * - Creates VPA Entity and returns the VPA ID, if Token Entity has no VPA associated to it
     * - Saves the customer VPA in the UPI Metadata Entity, if not present already
     *
     * @param Metadata $metadata
     * @param Token\Entity $token
     * @param string|null $vpa
     * @return mixed|null VPA ID (only if the VPA Entity is created)
     */
    protected function updateMetadataAndCreateVpaEntityIfApplicable(UpiMetadata\Entity $metadata, Token\Entity $token, ?string $vpa)
    {
        $vpaId = null;

        if (($metadata->isFlowIntent() === true) and
            (empty($vpa) === false))
        {
            if (empty($metadata->getVpa()) === true)
            {
                $metadata->setVpa($vpa);
            }

            if (is_null($token->getVpaId()) === true)
            {
                $vpaEntity = $this->createVpaEntity([
                    Vpa\Entity::VPA => $vpa
                ]);

                $vpaId = $vpaEntity[Vpa\Entity::ID];
            }
        }

        return $vpaId;
    }

    protected function processUpiRecurringFailureIfApplicable(Entity $payment, $data)
    {
        if ($payment->isUpiRecurring() === false)
        {
            return;
        }

        if ($payment->isUpiAutoRecurring() === true)
        {
            return;
        }

        try
        {
            $this->updateRecurringEntitiesForUpiIfApplicable($payment, $data);

        }
        catch(\Throwable $e)
        {
            if ($payment->getGateway() === Payment\Gateway::SHARP)
            {
                return;
            }

            $this->trace->traceException(
                $e,
                null,
                TraceCode::PAYMENT_UPI_METADATA_SAVE_FAILED,
                $data);
        }

    }

    protected function modifyRecurringDebitInputForUpi($mandate, array & $input, array $data)
    {
        if ($mandate instanceof Mandate)
        {
            // TODO:: Rename the upi_mandate key to just mandate
            $input['upi_mandate'] = $mandate->toArray();
        }

        $input['upi']['expiry_time'] = 5;
    }

    public function checkUpiAutopayIncreaseDebitRetry($paymentId, $merchantId,  $upi = null)
    {
        $app = \App::getFacadeRoot();

        $variant = $app['razorx']->getTreatment($merchantId,
            Merchant\RazorxTreatment::UPI_AUTOPAY_INCREASE_DEBIT_RETRIES,
            $app['rzp.mode'],
            3
        );

        $variant = strtolower($variant);

        // if razorx is on for 100% traffic
        if ($variant === 'on100')
        {
            return true;
        }

        $redisKey = "upi_autopay_debit_retry_" . $paymentId . "_" . $merchantId;
        $redisVal = $app['redis']->get($redisKey);

        if($redisVal === "1")
        {
            return true;
        }

        if($variant === 'on' and $upi !== null and $upi['gateway_data']['ano'] === 1)
        {
            $app['trace']->info(
                TraceCode::UPI_RECURRING_DEBIT_RETRY,
                [
                    'payment_id' => $paymentId,
                    'merchant_id' => $merchantId,
                ]);
            $ttl = 50 * 60 * 60; // 50 hours in seconds
            $app['redis']->set($redisKey, true, 'ex', $ttl, 'nx');
            return true;
        }

        return false;
    }

    /**
     * Sets the Flow in GatewayData in UPI Mandate, if not already set
     *
     * @param Mandate $upiMandate
     * @param string|null $flow
     */
    protected function updateUpiMandateFlowIfApplicable(UpiMandate\Entity $upiMandate, ?string $flow): void
    {
        $gatewayData = $upiMandate->getGatewayData() ?? [];

        if ((isset($flow) === true) and
            (isset($gatewayData[UpiMandate\Entity::FLOW]) === false))
        {
            $upiMandate->setFlow($flow);
        }
    }
}
