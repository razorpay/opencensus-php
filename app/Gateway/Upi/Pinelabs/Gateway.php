<?php

namespace RZP\Gateway\Upi\Pinelabs;

use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Exception\LogicException;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway
{

    use AuthorizeFailed;

    use CommonGatewayTrait;

    const ACQUIRER = 'pinelabs';

    protected $gateway = Payment\Gateway::PINELABS;

    protected $map = [];

    public function authorize(array $input)
    {
        /**
         * Processing authorize requests for upi payment method pinelabs with upi common trait.
         * Checking method if upi then send to Mozart else throw exception.
         */
        parent::authorize($input);

        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }
        throw new LogicException('Payment method is not upi, request unable to be processed via upi authorize action');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        throw new LogicException('UPI callback action not supported for this gateway');
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $method = $input = $verify->input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiSendPaymentVerifyRequest($verify);
        }

        throw new LogicException('Invalid payment method, mozart verify request call failed');
    }

    public function verify(array $input)
    {
        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            parent::verify($input);

            $verify = new Verify($this->gateway, $input);

            return $this->runPaymentVerifyFlow($verify);
        }

        throw new LogicException('Invalid payment method, mozart verify request failed');
    }

    protected function verifyPayment($verify)
    {
        $method = $verify->input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiVerifyPayment($verify);
        }

        throw new LogicException('Invalid payment method, payment verification failed');
    }

    public function getPaymentToVerify(Verify $verify)
    {
        if ($verify->input['payment']['method'] === Payment\Method::UPI)
        {
            $gatewayPayment = $this->upiGetRepository()->findByPaymentIdAndActionOrFail(
                $verify->input['payment']['id'], Action::AUTHORIZE);

            $verify->payment = $gatewayPayment;

            return $gatewayPayment;
        }

        return parent::getPaymentToVerify($verify);
    }
}