<?php

namespace RZP\Gateway\Upi\BilldeskOptimizer;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Exception\LogicException;
use RZP\Gateway\Upi\BilldeskOptimizer\Fields;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use CommonGatewayTrait;

    const ACQUIRER = "billdesk_optimizer";

    protected $gateway = Payment\Gateway::BILLDESK_OPTIMIZER;

    protected $map = [];

    public function authorize(array $input)
    {
        /**
         * Processing authorize requests for upi payment method billdesk_optimizer with upi common trait.
         * Checking method if upi then send to Mozart else throw exception.
         */
        parent::authorize($input);

        $method = $input["payment"]["method"];

        if ($method === Payment\Method::UPI) {
            return $this->upiAuthorize($input);
        }
        throw new LogicException(
            "Payment method is not upi, request unable to processed via upiAuthorize"
        );
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            $mozart = $this->getUpiMozartGatewayWithModeSet();

            $result = $mozart->sendUpiMozartRequest($input, TraceCode::GATEWAY_PAYMENT_CALLBACK, 'pay_verify');

            $input['gateway'] = $result;

            return $this->upiCallback($input);
        }
        throw new LogicException('Payment method is not upi, request unable to processed via upiCallback');
    }

    public function preProcessServerCallback($input): array
    {
        $mozart = $this->getUpiMozartGatewayWithModeFromEnvironment();
        $result = $mozart->sendUpiMozartRequest(
            $input,
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            "pre_process"
        );
        $input["paymentId"] = $result["data"]["paymentId"];
        return $input;
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        return $response["paymentId"];
    }

    public function postProcessServerCallback($input, $exception = null)
    {
        if ($exception === null) {
            return [
                "success" => true,
            ];
        }

        return [
            "success" => false,
        ];
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $method = $input = $verify->input["payment"]["method"];

        if ($method === Payment\Method::UPI) {
            return $this->upiSendPaymentVerifyRequest($verify);
        }
    }

    public function verify(array $input)
    {
        $method = $input["payment"]["method"];

        if ($method === Payment\Method::UPI) {
            parent::verify($input);

            $verify = new Verify($this->gateway, $input);

            $this->runPaymentVerifyFlow($verify);
        }
    }

    protected function verifyPayment($verify)
    {
        $method = $verify->input["payment"]["method"];

        if ($method === Payment\Method::UPI) {
            $this->upiVerifyPayment($verify);
        }
    }

    public function getPaymentToVerify(Verify $verify)
    {
        if ($verify->input["payment"]["method"] === Payment\Method::UPI) {
            $gatewayPayment = $this->upiGetRepository()->findByPaymentIdAndActionOrFail(
                $verify->input["payment"]["id"],
                Action::AUTHORIZE
            );

            $verify->payment = $gatewayPayment;

            return $gatewayPayment;
        }
        return parent::getPaymentToVerify($verify);
    }
}
