<?php

namespace RZP\Gateway\Base;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Verify\Action;
use RZP\Models\Payment\Processor;
use RZP\Models\Payment\Action as PaymentAction;
use RZP\Models\Payment\Processor\App as AppMethod;

trait AuthorizeFailed
{
    /**
     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     */
    public function authorizeFailed(array $input)
    {
        $e = null;

        try
        {
            $gateway = $input[Constants\Entity::PAYMENT][Entity::GATEWAY];
            $method  = $input[Constants\Entity::PAYMENT][Entity::METHOD];

            // If payment went via card payment service then call do verification through card payment service
            if (($input[Constants\Entity::PAYMENT][Entity::CPS_ROUTE] === Entity::CARD_PAYMENT_SERVICE))
            {
                $this->app['card.payments']->action($gateway, PaymentAction::VERIFY, $input);
            }
            elseif (($input[Constants\Entity::PAYMENT][Entity::CPS_ROUTE] === Entity::NB_PLUS_SERVICE))
            {
                $this->app['nbplus.payments']->action($method, $gateway, PaymentAction::VERIFY, $input);
            }
            else
            {
                $this->verify($input);
            }
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
                [
                    'message'    => 'Payment verification failed. Now converting to authorized',
                    'payment_id' => $input['payment']['id']
                ]);

            if ($e->getAction() === Action::RETRY)
            {
                //
                // When the response returned is a null, we throw
                // a PaymentVerificationException with Action::RETRY
                // and therefore we must return from here with an exception
                // result without processing the rest of this flow
                //
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                    null,
                    null,
                    [],
                    $e);
            }
        }

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not',
                null,
                $input['payment']);
        }

        $verify = $e->getVerifyObject();

        return $this->authorizeFailedPayment($verify);
    }

    /**
     * @param $verify
     * @return array
     * @throws Exception\LogicException
     */
    protected function authorizeFailedPayment($verify)
    {
        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true))
        {
            // This is gateway entity of the payment
            $gatewayPayment = $verify->payment;

            if ($gatewayPayment !== null)
            {
                $content = $verify->verifyResponseContent;

                $this->updateGatewayEntityLateAuthorized($gatewayPayment, $content);
            }

            if ((empty($verify->input['payment']) === false) and
                ($verify->input['payment']['method'] === Payment\Method::UPI) and
                ($verify->input['payment']['recurring'] === true))
            {
                return $this->extractUpiRecurringMandateAndPaymentProperties($gatewayPayment, $verify);
            }

            $response = $this->extractPaymentsProperties($gatewayPayment);

            if ((empty($verify->input['payment']) === false) and
                ($verify->input['payment']['method'] === Payment\Method::APP) and
                ($verify->input['payment']['wallet'] === AppMethod::CRED))
            {
                if(isset($verify->verifyResponseContent['data']) === true)
                {
                    $response['data'] = $verify->verifyResponseContent['data'];
                }
            }

            if ($verify->amountMismatch === true)
            {
                if ((is_string($verify->currency) === true) and
                    (is_integer($verify->amountAuthorized) === true))
                {
                    $response[Entity::CURRENCY]             = $verify->currency;
                    $response[Entity::AMOUNT_AUTHORIZED]    = $verify->amountAuthorized;
                }
                else
                {
                    throw new Exception\LogicException(
                        'For gateways with amountMismatch, currency and amountAuthorized are mandatory',
                        null,
                        ['payment' => $verify->input['payment']]);
                }
            }

            return $response;
        }

        throw new Exception\LogicException(
            'Should not have reached here',
            null,
            ['payment' => $verify->input['payment']]);
    }

    protected function extractPaymentsProperties($gatewayPayment)
    {
        $response = [];

        if (method_exists($gatewayPayment, 'getAuthCode') === true)
        {
            $response['acquirer'][Entity::REFERENCE2] = $gatewayPayment->getAuthCode();
        }

        if (method_exists($gatewayPayment, 'getBankPaymentId') === true)
        {
            $response['acquirer'][Entity::REFERENCE1] = $gatewayPayment->getBankPaymentId();

            // For api based emandate initial payments, if late authorized,
            // we need to update the token status to confirmed
            if (($this->input['payment']['method'] === Payment\Method::EMANDATE) and
                ($this->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL))
            {
                if (method_exists($this, 'getRecurringData') === true)
                {
                    $recurringData = $this->getRecurringData($gatewayPayment);

                    $response = array_merge($response, $recurringData);
                }
            }
        }

        if (method_exists($gatewayPayment, 'getVpa') === true)
        {
            $response['acquirer'][Entity::VPA] = $gatewayPayment->getVpa();
        }

        if (method_exists($gatewayPayment, 'getNpciReferenceId') === true)
        {
            $response['acquirer'][Entity::REFERENCE16] = $gatewayPayment->getNpciReferenceId();
        }

        return $response;
    }

    protected function updateGatewayEntityLateAuthorized($gatewayPayment, $content)
    {
        if ($this->shouldMapLateAuthorized === true)
        {
            $content = $this->getMappedAttributes($content);
        }

        $gatewayPayment->fill($content);

        $gatewayPayment->saveOrFail();
    }
}
