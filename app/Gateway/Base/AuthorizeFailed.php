<?php

namespace RZP\Gateway\Base;

use RZP\Trace\TraceCode;
use RZP\Exception;

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
                ['message' => 'Payment verification failed. Now converting to authorized']);
        }

        if ($e === null)
        {sd($e);
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not',
                null,
                $input);
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
            $payment = $verify->payment;
            $payment->fill($verify->verifyResponseContent);
            $payment->saveOrFail();
        }
        else
        {
            throw new Exception\LogicException(
                'Should not have reached here',
                null,
                ['payment' => $verify->input['payment']]);
        }

        return true;
    }
}
