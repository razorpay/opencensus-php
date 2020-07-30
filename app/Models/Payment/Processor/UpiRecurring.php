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
use RZP\Models\Payment\Entity;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\UpiMetadata;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Reminders\ReminderProcessor;
use RZP\Models\UpiMandate\Entity as Mandate;

trait UpiRecurring
{
    public function processAutoRecurringPreDebitForUpi(Payment\Entity $payment)
    {
        $mandate = $this->repo->upi_mandate->findByTokenId($payment->getTokenId());

        $this->validateAutoRecurringForUpiBeforePreDebit($payment, $mandate);

        $input = [
            'action'        => Payment\Action::PRE_DEBIT,
            'gateway'       => $payment->getGateway(),
            'terminal'      => $payment->terminal,
            'upi_mandate'   => $mandate,
            'payment'       => $payment,
            'merchant'      => $payment->merchant,
            'upi'           => $payment->getUpiMetadata()->toArray(),
        ];

        $this->mutex->acquireAndRelease($payment->getId(),
            function() use ($input, $payment, $mandate) {
                try
                {
                    // Before making gateway call, we will change the status
                    $metadata = $payment->getUpiMetadata();
                    $metadata->setInternalStatus(UpiMetadata\InternalStatus::PRE_DEBIT_INITIATED);
                    (new UpiMetadata\Core)->update($metadata);

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

        $input = [];
        $gatewayInput = [
            'selected_terminal_ids' => [$payment->getTerminalId()],
            'upi_mandate'           => $mandate->toArray(),
        ];

        $this->modifyAutoRecurringForUpiIfApplicable($payment, $input, $gatewayInput);

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

        Customer\Entity::verifyIdAndStripSign($customerId);

        $payment = $this->repo->payment->getByTokenIdAndCustomerId($token['id'], $customerId);

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

    protected function updateTokenOnAuthorizedForUpiRecurring(
        Token\Entity $token, array $gatewayData, Payment\Entity $payment)
    {
        if ($payment->isSecondRecurring() === true)
        {
            $this->updateTokenOnAuthorizedForUpiSecondRecurring($token, $payment);
        }

        if ($token->getRecurringStatus() !== 'initiated' and ($token->getRecurringStatus() !== 'not_applicable'))
        {
            //
            // We don't throw an exception here because this flow
            // is called while marking the payment as authorized.
            // We don't want to mess with payment being authorized!
            //
            $this->trace->critical(
                TraceCode::TOKEN_RECURRING_STATUS_ALREADY_SET,
                [
                    'token'        => $token->toArray(),
                    'gateway_data' => $gatewayData
                ]);

            return;
        }

        (new Token\Core)->updateTokenForUpi($token, $gatewayData);
    }

    protected function updateTokenOnAuthorizedForUpiSecondRecurring(Token\Entity $token, Payment\Entity $payment)
    {
        if ($token->getRecurringStatus() !== Token\RecurringStatus::CONFIRMED)
        {
            //
            // We don't throw an exception here because this flow
            // is called while marking the payment as authorized.
            // We don't want to mess with payment being authorized!
            //
            $this->trace->critical(
                TraceCode::TOKEN_RECURRING_STATUS_ALREADY_SET,
                [
                    'token'        => $token->toArray(),
                ]);

            return;
        }

        $token->setRecurringStatus(Token\RecurringStatus::PAID);

        return;
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

        if ($payment->getAmount() !== $order->getAmount())
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
        //TODO:: This has to be added in the auto recurring PR for upi.
    }

    protected function validateAutoRecurringForUpiBeforePreDebit(Entity $payment, Mandate $mandate)
    {
        // TODO: Add predebit validation, these will go as logic exception for now
    }

    protected function modifyAutoRecurringForUpiIfApplicable(Entity $payment, array & $input, array & $gatewayInput)
    {
        // Do nothing for other payments
        if ($payment->isUpiAutoRecurring() === false)
        {
            return;
        }

        // If payment does not exists, basic check no need to set verifiable
        if ($payment->exists === false)
        {
            $payment->setNonVerifiable();
        }

        $metadata = $this->getUpiMetadataForPayment($payment);

        if ($metadata->exists === false)
        {
            $metadata->setMode(UpiMetadata\Mode::AUTO);
            $metadata->setType(UpiMetadata\Type::RECURRING);

            // Adding 30 seconds as buffer
            $metadata->setRemindAt($metadata->freshTimestamp() + 30);
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_PENDING_FOR_PRE_DEBIT);
        }

        // Now for gateway input part, we need to attack extra fields to the upi block
        $gatewayInput['upi'] = $metadata->toArray();
    }

    protected function shouldHitGatewayForAutoRecurringForUpi(Entity $payment, array $gatewayInput)
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

    protected function shouldAutoReccuringSkipAuthorizeForUpi(Entity $payment, array $data): bool
    {
        // Any payment which is not UPI Auto Recurring can be authorized
        if (($payment->isUpiAutoRecurring()) === false)
        {
            return false;
        }

        // Data will be empty when gateway call is not made, or there is some issue with gateway integration
        // In both cases we can leave the payment in created state, it can be picked again by cron
        $metadata = $this->getUpiMetadataForPayment($payment);

        // When the metadata status is ReminderInProgressForAuthorized or AuthorizeInitiated
        // Then we will check for internal status if any sent from gateway
        if (($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE)) or
            ($metadata->isInternalStatus(UpiMetadata\InternalStatus::AUTHORIZE_INITIATED)))
        {
            $internalStatus = $data['upi']['internal_status'] ?? null;

            // If gateway is explicitly telling that the payment is authorized at gateways end
            // We will not skip authorize for those cases
            if ($internalStatus === UpiMetadata\InternalStatus::AUTHORIZED)
            {
                return false;
            }
        }

        return true;
    }

    protected function shouldInitialReccuringSkipAuthorizeForUpi(Entity $payment, array $data): bool
    {
        if ($this->isFirstUpiRecurringPayment($payment) === false)
        {
            return false;
        }

        // Even if this is first recurring payment, for sharp we do not play two callback attempts
        // thus we will have make the payment authorized in the first callback itself.
        if ($payment->isGateway(Payment\Gateway::SHARP) === true)
        {
            return false;
        }

        $mandate = array_get($data, 'mandate');

        // If the first debit never ran for this payment, we have to skip the authorize
        if ((isset($mandate['status'])) and
            ($mandate['status'] === UpiMandate\Status::CONFIRMED))
        {
            return true;
        }

        // If the first debit is completed then we can authorize the payment and also update the token
        return false;
    }

    // This function will be called in two cases where Authorize is called.
    // In first case when merchant has sent a request where we are going to create an auto recurring payment,
    // the flow goes like this.
    // 1. First we will have payment created
    // 2. We will make a RS call and get the reminder_id
    // In Second case where RS calls for authorization and a callback is expected from gateway
    protected function processAutoRecurringCreatedForUpi(Entity $payment, array $data)
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
        // Second case where the response is coming from gateway
        else if ($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE))
        {
            $upiEdit = array_only($data['upi'], $metadata->getFillable());

            $metadata->edit($upiEdit);
            $metadata->setInternalStatus(UpiMetadata\InternalStatus::AUTHORIZE_INITIATED);
            $metadata->setRemindAt(null);

            (new UpiMetadata\Core)->update($metadata);

            // Now since we are expecting a callback from gateway, we can enable the verify for payment
            // But since it is auto recurring payment and neither customer not merchant is blocked on this
            // We can later increase the verify for the payment.
            $payment->setVerifyAt(Carbon::now()->addMinutes(2)->getTimestamp());

            $this->repo->saveOrFail($payment);
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

        return ['razorpay_payment_id' => $payment->getPublicId()];
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

    protected function shouldDebitRecurringPaymentForUpi(array $input, array $data)
    {
        return (($this->isFirstUpiRecurringPayment($input['payment']) === true) and
                ($this->isMandateCreateCallback($data) === true));
    }

    protected function isFirstUpiRecurringPayment($payment): bool
    {
        return ($payment['method'] === 'upi' and $payment['recurring_type'] === 'initial');
    }

    protected function isMandateCreateCallback(array $data)
    {
        if (isset($data['mandate']) === true)
        {
            return true;
        }

        return false;
    }

    protected function updateRecurringMandateForUpiIfApplicable($payment, array $data)
    {
        $orderId = array_pull($data['mandate'], 'order_id');

        $upiMandate = $this->repo->upi_mandate->findByOrderId($orderId);

        // Mandate Status must be confirmed at this stage
        return $this->updateUpiMandateOnCallback($upiMandate, $data['mandate']);
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

    protected function updateUpiMandateOnCallback(Mandate $upiMandate, $attributes)
    {
        $status = array_pull($attributes, 'status');

        $upiMandate->edit($attributes);

        if ($status !== null)
        {
            $upiMandate->setStatus($status);
        }

        $this->repo->saveOrFail($upiMandate);

        return $upiMandate;
    }
}
