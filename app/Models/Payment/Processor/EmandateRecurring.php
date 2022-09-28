<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use Razorpay\Trace\Logger as Trace;

trait EmandateRecurring
{
    /**
     * shouldSkipAuthorizeOnRecurringForEmandate : Check to see if emandate
     * token should be updated for API based gateways having async token flow,
     * when payment is already authorized.
     * This flow is similar to file based emandate flow.
     *
     * Called from updateAndNotifyPaymentAuthorized.
     * @param Entity $payment
     * @param array $data
     */
    protected function shouldSkipAuthorizeOnRecurringForEmandate(Entity $payment, array $data): bool
    {
        if (($payment->isEmandateRecurring() === false) or
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === false))
        {
           return false;
        }

        // For Auto Recurring
        if ($payment->isEmandateAutoRecurring() === true)
        {
            // Only skip authorize flow if gateway has not processed payment.
            // We will wait for webhook in this case.
            if ((isset($data['additional_data']) === true) and
                (isset($data['additional_data']['gateway_payment_status']) === true) and 
                ($data['additional_data']['gateway_payment_status'] === 'pending'))
            {
                return true;
            }

            return false;
        }

        // Other checks for initial payment
        // 
        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            return false;
        }

        //
        // If payment is authorized and token is not yet updated, it means 
        // gateway is sending a webhook with token status asynchronously. 
        // In such cases, just update the token.
        //
        if (($payment->isEmandate() === true) and
            ($token->isRecurring() === false) and 
            ($payment->hasBeenAuthorized() === true))
        {
            return true;
        }

        return false;
    }

    /**
     * updateRecurringEntitiesForEmandateIfApplicable : Update token recurring 
     * parameters.
     *
     * Called from updateAndNotifyPaymentAuthorized.
     * @param Entity $payment
     * @param array $data
     * @param bool $wasFailed
     */
    protected function updateRecurringEntitiesForEmandateIfApplicable(Entity $payment, array $data, bool $wasFailed = false)
    {
        // For Auto Recurring
        if ($payment->isEmandateAutoRecurring() === true)
        {
            $this->updatePaymentEntityForEmandateAsyncRecurringPayment($payment, $data);
            return;
        }

        // only for emandate payments
        if (($payment->isEmandateRecurring() === false) or
            ($payment->isRecurringTypeInitial() === false) or
            ($payment->isSecondRecurring() === true) or
            ($payment->hasBeenAuthorized() === false) or
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === false))
        {
           return;
        }

        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            return;
        }

        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_TOKEN,
            [
                'payment_id'      => $payment->getId(),
                'token_id'        => $payment->getTokenId(),
                'global_token_id' => $payment->getGlobalTokenId(),
                'gateway_data'    => $data,
            ]);

        // Token used count should not be incremented here, 
        // since this flow is there to update only the recurring parameters

        $oldRecurringStatus = $token->getRecurringStatus();

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $data);

        if ($token->getTerminalId() === null)
        {
            $token->terminal()->associate($payment->terminal);
        }

        $this->repo->saveOrFail($token);

        $gatewayRecurringStatus = $data[Token\Entity::RECURRING_STATUS];
        
        if ($gatewayRecurringStatus === Token\RecurringStatus::REJECTED)
        {
            $this->refundPayment($payment);
        }
        elseif ($gatewayRecurringStatus === Token\RecurringStatus::CONFIRMED)
        {
            $this->updateGatewayTokenAttributes($payment, $token);
        }
        
        $this->eventTokenStatus($token, $oldRecurringStatus);
    }

    private function updateGatewayTokenAttributes(Entity $payment, Token\Entity $token)
    {
        $reference = $payment->getReferenceForGatewayToken();

        $gatewayTokens = $this->repo->gateway_token->findByTokenAndReference($token, $reference);

        $gateway = $payment->getGateway();

        $gatewayTokensToUpdate = $gatewayTokens->filter(
                                        function($gatewayToken) use ($gateway)
                                        {
                                            return ($gatewayToken->getGateway() === $gateway);
                                        });


        if ($gatewayTokensToUpdate->count() === 0)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_TOKEN_MISMATCH,
                [
                    'payment_id'    => $payment->getId(),
                    'token_id'      => $token->getId(),
                    'gateway'       => $gateway,
                    'reference'     => $reference,
                ]);

            // GT should have already been created if the flow reaches here.
            // Just exit if that is the case.
            return;
        }
        else
        {
            // There will be only one for sure.
            // There can't be more than 1 because, the only time we create is
            // when there doesn't exist a single gateway_token of the gateway.
            // All other cases, we only update the existing one. Hence, there
            // can never be more than one gateway_token of a gateway.

            if ($gatewayTokensToUpdate->count() > 1)
            {
                $this->trace->critical(
                    TraceCode::GATEWAY_TOKEN_TOO_MANY_PRESENT,
                    [
                        'count'         => $gatewayTokensToUpdate->count(),
                        'payment_id'    => $payment->getId(),
                        'token_id'      => $token->getId()
                    ]);

                // This is unexpected behaviour and should never
                // happen and hence just returning back from here.
                return;
            }

            $gatewayTokenToUpdate = $gatewayTokensToUpdate->first();

            $gatewayTokenToUpdate->setRecurring($token->isRecurring());

            $this->repo->saveOrFail($gatewayTokenToUpdate);
        }
    }

    private function refundPayment(Entity $payment)
    {
        if ($payment->isAuthorized() === false)
        {
            $this->trace->critical(TraceCode::PAYMENT_RECURRING_INVALID_STATUS,
                [
                    'status' => $payment->getStatus(),
                    'payment_id' => $payment->getId(),
                ]);

            return;
        }

        $processor = new Processor($this->merchant);

        // based on experiment, refund request will be routed to Scrooge
        return $processor->refundAuthorizedPayment($payment);
    }

    /**
     * This is called when gateway returns a pending status. 
     * In such cases we return the payment id and other details to merchant.
     * For payment to reach terminal status, we wait for webhook from gateway.
     *
     * Called from processPaymentFinal.
     * @param Entity $payment
     * @param array $data
     */
    protected function processRecurringCreatedForEmandateAsyncGateway(Entity $payment, array $data)
    {
        if (($payment->isEmandateAutoRecurring() === true) and
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === true))
        {
            $data = ['razorpay_payment_id' => $payment->getPublicId()];

            if (($payment->hasOrder() === true) and
                ($this->app['basicauth']->isProxyOrPrivilegeAuth() === false) and
                ($this->app->runningInQueue() === false))
            {
                $this->fillReturnDataWithOrder($payment, $data);
            }

            return $data;
        }

        throw new Exception\LogicException('Should not be called for any payment other than Emandate Auto Recurring');
    }

    // if we got a webhook and payment is still in pending status, 
    // then just log it and skip authorize flow.
    // No need to update any entities.
    protected function updatePaymentEntityForEmandateAsyncRecurringPayment(Entity $payment, array $data)
    {
        if (($payment->isCreated() === true) and
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === true) and
            (isset($data['additional_data']) === true) and
            (isset($data['additional_data']['gateway_payment_status']) === true) and 
            ($data['additional_data']['gateway_payment_status'] === 'pending'))
        {
            $this->trace->info(
                TraceCode::PAYMENT_RECURRING_DEBIT_STATUS_UNCHANGED,
                [
                    'payment_id'      => $payment->getId(),
                    'token_id'        => $payment->getTokenId(),
                    'gateway_data'    => $data,
                ]);
        }
    }
}