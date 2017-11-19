<?php

namespace RZP\Gateway\Base;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment\Verify\Result;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Verify\Action;

trait AuthorizeFailed
{
    public function authorizeFailed(array $input)
    {
        $e = null;

        try
        {
            $this->verify($input);
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

    protected function authorizeFailedPayment($verify)
    {
        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true))
        {
            // This is gateway entity of the payment
            $gatewayPayment = $verify->payment;
            $gatewayPayment->fill($verify->verifyResponseContent);
            $gatewayPayment->saveOrFail();

            return $this->extractPaymentsProperties($gatewayPayment);
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

        return $response;
    }
}
