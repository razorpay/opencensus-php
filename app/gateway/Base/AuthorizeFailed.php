<?php

namespace Gateway\Base;

use EE\Exception;
use Trace\TraceCode;

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

        $verify = $e->getVerifyObject();

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not',
                $verify->getDataToTrace());
        }

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true))
        {
            $payment = $verify->payment;
            $payment->fill($verify->verifyResponseContent);
            $payment->saveOrFail();
        }
        else
        {
            throw new Exception\LogicException(
                'Should not have reached here');
        }

        return true;
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                    $input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $payment;

        return $payment;
    }
}