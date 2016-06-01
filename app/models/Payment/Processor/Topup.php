<?php

namespace Models\Payment\Processor;

use EE\Exception;
use Models\Payment;
use Models\Customer;
use Models\Merchant;
use Trace\TraceCode;

trait Topup
{
    public function topup($id, $input)
    {
        $payment = $this->retrieve($id);

        $gatewayInput = [];

        try
        {
            $this->prePaymentTopupProcessing($payment, $input, $gatewayInput);

            return $this->callGatewayTopup($payment, $gatewayInput);
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_TOPUP_FAILURE);

            throw $e;
        }

        assert(false, 'Should not reach here.');
    }

    protected function callGatewayTopup($payment, array $data)
    {
        $request = $this->callGatewayFunction(
                                        Payment\Action::TOPUP,
                                        $data);

        if ($request !== null)
        {
            return $this->getPaymentGatewayRequestData($request, $payment);
        }

        assert(false, 'Should not reach here.');
    }

    protected function prePaymentTopupProcessing($payment, $input, array & $gatewayInput)
    {
        $canTopup = $this->callGatewayFunction('canTopup', []);

        if ($canTopup === false)
        {
            throw new Exception\BaseException(
                'Gateway doesn\'t support topup');
        }

        //
        // Call gateway input
        //
        $gatewayInput['gateway']  = $input;

        $gatewayInput['payment']  = $payment->toArray();

        $customer = $payment->customer;

        //
        // Check for mobikwik wallet, ideally there is no need of if-block
        // Mobikwik topup works without customer (for now)
        //
        if ($payment->getWallet() !== Wallet::MOBIKWIK)
        {
            if ($customer === null)
            {
                $contact = $this->getFormattedContact($gatewayInput['payment']['contact']);

                $customer = (new Customer\Repository)
                                        ->findByContactForMerchant(
                                            $contact, Merchant\Account::SHARED_ACCOUNT);

            }

            $gatewayInput['customer'] = $customer->toArray();

            $gatewayInput['token']    = $this->retrieveToken($gatewayInput);

            if ($gatewayInput['token'] === null)
            {
                throw new Exception\BaseException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
            }
        }

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }
}