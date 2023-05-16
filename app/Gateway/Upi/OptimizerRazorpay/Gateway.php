<?php

namespace RZP\Gateway\Upi\OptimizerRazorpay;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Exception\LogicException;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway{
    use AuthorizeFailed;

    use CommonGatewayTrait;

    protected $gateway = Payment\Gateway::OPTIMIZER_RAZORPAY;
    public function authorize(array $input){
        parent::authorize($input);
        $method = $input[Fields::PAYMENT][Fields::METHOD];
        if ($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }
        throw new LogicException('Invalid Payment method, authorize request failed');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $method = $input[Fields::PAYMENT][Fields::METHOD];

        if ($method === Payment\Method::UPI)
        {
            $mozart = $this->getUpiMozartGatewayWithModeSet();

            $result = $mozart->sendUpiMozartRequest($input, TraceCode::GATEWAY_PAYMENT_CALLBACK, 'pay_verify');

            $input[Fields::GATEWAY] = $result;

            return $this->upiCallback($input);
        }

        throw new LogicException('Invalid Payment method, callback request failed');
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        $notes = $response[Fields::PAYLOAD][Fields::PAYMENT][Fields::ENTITY][Fields::NOTES];

        $this->trace->info(TraceCode::OPTIMISER_RAZORPAY_CALLBACK_PAYMENT_ID, [
            Fields::GATEWAY => $gateway,
            Fields::NOTES   => $notes,
        ]);

        return $notes[Fields::RECEIPT];
    }

    public function preProcessServerCallback($input): array
    {
        return $input;
    }

    public function postProcessServerCallback($input, $exception = null)
    {
        if ($exception === null)
        {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];

    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $method = $input = $verify->input[Fields::PAYMENT][Fields::METHOD];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiSendPaymentVerifyRequest($verify);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        $method = $verify->input[Fields::PAYMENT][Fields::METHOD];

        if ($method === Payment\Method::UPI)
        {
            $this->upiVerifyPayment($verify);
        }
    }

    public function getPaymentToVerify(Verify $verify)
    {
        if ($verify->input[Fields::PAYMENT][Fields::METHOD] === Payment\Method::UPI)
        {
            $gatewayPayment = $this->upiGetRepository()->findByPaymentIdAndActionOrFail(
                $verify->input[Fields::PAYMENT]['id'], Action::AUTHORIZE);

            $verify->payment = $gatewayPayment;

            return $gatewayPayment;
        }
        return parent::getPaymentToVerify($verify);
    }
}
