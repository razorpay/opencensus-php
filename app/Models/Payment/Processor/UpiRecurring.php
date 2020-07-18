<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Payment\Entity;
use RZP\Models\Customer\Token;
use Razorpay\Trace\Logger as Trace;

trait UpiRecurring
{
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

        $this->mutex->acquireAndRelease($this->getMandateUpdateMutexResource($token),
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

    protected function getMandateUpdateMutexResource(Token\Entity $token): string
    {
        return 'mandate_update_' . $token->getId();
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

    protected function modifyAutoRecurringForUpiIfApplicable(Entity $payment, array $data = null, array $input = [])
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
    }

    protected function shouldAutoReccuringAuthorizedForUpi(Entity $payment, array $data): bool
    {
        // Any payment which is not UPI Auto Recurring can be authorized
        if (($payment->isUpiAutoRecurring()) === false)
        {
            return true;
        }

        // Data will be empty when gateway call is not made, or there is some issue with gateway integration
        // In both cases we can leave the payment in created state, it can be picked again by cron
        if (empty($data) === true)
        {
            return false;
        }

        return true;
    }

    protected function processAutoRecurringCreatedForUpi(Entity $payment, array $data)
    {
        // What to process here?

        return ['razorpay_payment_id' => $payment->getPublicId()];
    }
}
