<?php

namespace RZP\Gateway\Upi\Cashfree;
use Request;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Exception\LogicException;
use RZP\Gateway\Upi\Cashfree\Fields;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway
{

    use AuthorizeFailed;

    use CommonGatewayTrait;

    const ACQUIRER = 'cashfree';

    protected $gateway = Payment\Gateway::CASHFREE;

    protected $shouldMapLateAuthorized = false;

    protected $map = [];

    public function authorize(array $input)
    {
        /**
         * Processing authorize requests for upi payment method cashfree with upi common trait.
         * Checking method if upi then send to Mozart else throw exception.
         */
        parent::authorize($input);

        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }
        throw new LogicException('Payment method is not upi, request unable to processed via upiAuthorize');
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
        if(isset($input["data"]["order"]["order_id"]) === true)
        {
            $headers = Request::header();
            $input["headers"]["signature"] = $headers["x-webhook-signature"][0] ?? '';
            $input["headers"]["timestamp"] = $headers["x-webhook-timestamp"][0] ?? '';
            $input["headers"]["webhook-version"] = $headers["x-webhook-version"][0] ?? '';
        }

        return $input;
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        //temporary fix until complete migration to V4 version
        if(isset($response["data"]["order"]["order_id"]) === true)
        {
            return $response["data"]["order"]["order_id"];
        }
        return $response[Fields::ORDER_ID];
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
        $method = $input = $verify->input['payment']['method'];

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
        $method = $verify->input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            $this->upiVerifyPayment($verify);
        }
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
