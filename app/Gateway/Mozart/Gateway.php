<?php

namespace RZP\Gateway\Mozart;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Mozart\Entity as MozartEntity;

class Gateway extends Base\Gateway
{
    protected $gateway = 'mozart';

    protected $map = [
        'data'      => Entity::RAW,
    ];

    public function authorize(array $input)
    {
        parent::action($input, Action::PAY_INIT);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input, Action::AUTHORIZE);

        return $response['next']['redirect'] ?? null;
    }

    public function otpGenerate(array $input)
    {
        return $this->authorize($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->verifyOtpAttempts($input['payment']);

        return $this->callback($input);
    }

    public function callback(array $input)
    {
        parent::action($input, Action::PAY_VERIFY);

        $gateway = $input['gateway'];

        $traceRes = $this->getRedactedData($gateway);

        $this->traceGatewayPaymentRequest($traceRes, $input, TraceCode::PAYMENT_CALLBACK_REQUEST );

        unset($input['gateway']);

        $input['gateway']['redirect'] = $gateway;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_RESPONSE);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $response, true, Action::AUTHORIZE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        if ($input['payment']['gateway'] === Payment\Gateway::BAJAJFINSERV)
        {
            $input['gateway']['pay_verify']['RequestID'] = $response['data']['RequestID'];

            $verifyResponse = $this->verify($input);

            return $verifyResponse;
        }

        if ($input['payment']['gateway'] === Payment\Gateway::NETBANKING_SIB)
        {
            $this->handleNetbankingSibCallback($input, $response);
        }

        return $response;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayRefundEntity($attributes, $input, $this->action);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $request = $this->getMozartRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
    }

    public function verifyRefund(array $input)
    {
        $this->input = $input;
        $this->action = Action::VERIFY_REFUND;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_REFUND_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);

    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $response;

        $verify->verifyResponse = null;

        $verify->verifyResponseBody = null;

        return $verify->verifyResponseContent;
    }

    protected function verifyPayment($verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($content['success'] === true)
        {
            $this->verifyPaymentWithGatewayResponse($verify);
        }
        else
        {
            $this->verifyNonExistentCase($verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        if ($input['payment']['gateway'] === Payment\Gateway::NETBANKING_SIB)
        {
            $content = $this->updateBankPaymentIdFromResponse($content, $verify->payment);
        }

        $this->updateGatewayPaymentEntity($verify->payment, $content, true, Action::AUTHORIZE);

        return $verify->status;
    }

    protected function verifyNonExistentCase($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $verify->gatewaySuccess = false;

        if (($payment === null) and ($input['payment']['status'] === 'failed'))
        {
            $verify->apiSuccess = false;
        }
        else if (($payment === null) or ($input['payment']['status'] !== Payment\Status::AUTHORIZED))
        {
            $verify->apiSuccess = false;
        }
        else if ($input['payment']['status'] === Payment\Status::AUTHORIZED)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function verifyPaymentWithGatewayResponse($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $verify->gatewaySuccess = true;

        if (($input['payment'][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($input['payment']['status'] === Payment\Status::CREATED))
        {
            $verify->status     = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = false;
        }
        else
        {
            $verify->apiSuccess = true;
        }
    }

    protected function getMozartRequestArray($input)
    {
        $input['terminal'] = $input['terminal']->toArrayWithPassword();

        $prevStepName = $this->getPreviousStepName($input['payment']['gateway']);

        $prevStepDB = $this->getPreviousStepForDB($input['payment']['gateway']);

        if ($prevStepName != null)
        {
            $input['gateway'][$prevStepName] = $this->getPreviousData($input, $prevStepDB);
        }

        if (($this->action === Action::VERIFY) and
            ($input['payment']['gateway'] === Payment\Gateway::NETBANKING_SIB) and
            (isset($input['gateway'][$prevStepName]['bank_payment_id']) === false))

        {
            $input['gateway'][$prevStepName]['bank_payment_id'] = '0';
        }

        $content['entities'] = $input;

        return $this->getMozartRequest($content, $input['payment']['gateway']);
    }

    protected function getMozartRequest($content, $gateway)
    {
        $baseUrl = $this->app['config']->get('applications.mozart.url');

        // temporary change given to external bank for testing
        if ($gateway === Payment\Gateway::NETBANKING_SIB)
        {
            $baseUrl = 'https://zeta-mozart.stage.razorpay.in/';
        }

        $url =  $baseUrl . 'payments/' . $gateway . '/v1/' . $this->action;

        $authentication = [
            'api',
            $this->app['config']->get('applications.mozart.password')
        ];

        return [
            'url' => $url,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Task-ID'    => $this->app['request']->getTaskId(),
            ],
            'content' => json_encode($content),
            'options' => [
                'auth' => $authentication
            ]
        ];
    }

    protected function getPreviousStepName($gateway)
    {
        $previousActionForData = [
            Payment\Gateway::NETBANKING_SIB => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_VERIFY,
            ],
            Payment\Gateway::BAJAJFINSERV => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_VERIFY,
                Action::REFUND => Action::PAY_VERIFY,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
        ];

        return $previousActionForData[$gateway][$this->action];
    }

    protected function getPreviousStepForDB($gateway)
    {
        $previousActionForStep = [
            Payment\Gateway::NETBANKING_SIB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
            Payment\Gateway::BAJAJFINSERV => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY => Action::AUTHORIZE,
                Action::REFUND => Action::AUTHORIZE,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
        ];
        return $previousActionForStep[$gateway][$this->action];
    }


    protected function getPreviousData($input, $prevActionForData)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], $prevActionForData);

        $jsonRaw = json_decode($gatewayPayment['raw'], true);
        return $jsonRaw;
    }

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        return $this->jsonToArray($response->body, true);
    }

    protected function getRedactedData($data)
    {
        unset($data['data']['Key']);

        unset($data['data']['enqinfo']['0']['Key']);

        unset($data['data']['enqinfo']['0']['MOBILENO']);

        unset($data['data']['MobileNo']);

        unset($data['data']['valkey']);

        unset($data['otp']);

        unset($data['data']['_raw']);

        return $data;
    }

    protected function createGatewayPaymentEntity($attributes, $input, $action)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $amount    = $input['payment']['amount'];
        $gateway   = $input['payment']['gateway'];

        $gatewayPayment->setAction($action);
        $gatewayPayment->setAmount($amount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes, $input, $action)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $refundId  = $input['refund']['id'];
        $refundAmount = $input['refund']['amount'];
        $gateway      = $input['payment']['gateway'];

        $gatewayPayment->setAction($action);
        $gatewayPayment->setRefundId($refundId);
        $gatewayPayment->setAmount($refundAmount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    public function handleNetbankingSibCallback($input, $callbackResponse)
    {
        $this->assertPaymentId($input['payment']['id'], $callbackResponse['data']['payment_id']);

        $this->assertAmount($input['payment']['amount'], (int) $callbackResponse['data']['amount']);

        // For verify callback
        $this->action = Action::VERIFY;

        $content['entities']['payment'] = $input['payment'];

        $content['entities']['terminal'] = $input['terminal'];

        $content['entities']['gateway']['pay_verify'] = $callbackResponse['data'];

        $request = $this->getMozartRequest($content, Payment\Gateway::NETBANKING_SIB);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $content['entities'], TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $content['entities'], TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $this->action = Action::PAY_VERIFY;
    }

    protected function updateGatewayPaymentEntityWithAction(
        MozartEntity $gatewayPayment,
        array $attributes,
        bool $mapped = true,
        $action = null)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        $action = $action == null ? $this->action : $action;

        $redactedRaw = $this->getRedactedData($attributes['raw']);

        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment->setAction($action);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function updateBankPaymentIdFromResponse($content, $gatewayPayment)
    {
        $rawResponse = $content['data']['_raw']['BODY'];

        $gatewayPaymentRaw = json_decode($gatewayPayment['raw'], true);

        if ($rawResponse === 'Transaction Completed Successfully')
        {
            $content['data']['bank_payment_id'] = $gatewayPaymentRaw['bank_payment_id'];
        }

        if (strpos($rawResponse, 'Transaction Completed Successfully. Bank Reference Number is') !== false)
        {
            $splitRawResponse = explode(' ', $rawResponse);

            $content['data']['bank_payment_id'] = $splitRawResponse[count($splitRawResponse) - 1];
        }

        return $content;
    }

    protected function getPaymentToVerify(Verify $verify)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $verify->input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }
}
