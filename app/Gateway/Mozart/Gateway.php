<?php

namespace RZP\Gateway\Mozart;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Entity as BaseEntity;
use RZP\Gateway\Base\VerifyResult;

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

        $traceReq = $this->getRedactedData($request);

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input);

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

        unset($input['gateway']);

        $input['gateway']['redirect'] = $gateway;

        $request = $this->getMozartRequestArray($input);

        $traceReq = $this->getRedactedData($request);

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_RESPONSE);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], 'pay_init');

        $this->gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $response, true);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        if ($input['payment']['gateway'] === Payment\Gateway::BAJAJFINSERV)
        {
            $input['gateway']['pay_verify']['RequestID'] = $response['data']['RequestID'];

            $verifyResponse = $this->verify($input);

            return $verifyResponse;
        }

        return $response;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getMozartRequestArray($input);

        $traceReq = $this->getRedactedData($request);

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayRefundEntity($attributes, $input);

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

        $traceReq = $this->getRedactedData($request);

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

        $traceReq = $this->getRedactedData($request);

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

        $this->updateGatewayPaymentEntity($verify->payment, $content);

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

        if ($input['payment']['status'] !== 'failed')
        {
            $verify->apiSuccess = true;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = false;
        }
    }

    protected function getMozartRequestArray($input)
    {
        $input['terminal'] = $input['terminal']->toArrayWithPassword();

        $prevStep = $this->getPreviousStep($input['payment']['gateway']);

        if ($prevStep != null)
        {
            $input['gateway'][$prevStep] = $this->getPreviousData($input, $prevStep);
        }

        //This is implemented for testing Bajaj as minimum payment is 3000 which is not supported on bajaj test cards.
        if($this->mode === Mode::TEST)
        {
            $input['payment']['amount'] = 100;
        }

        $content['entities'] = $input;

        $baseUrl = $this->app['config']->get('applications.mozart.url');

        $url =  $baseUrl . 'payments/' . $this->gateway. '/v1/' . $this->action;

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

    protected function getPreviousStep($gateway)
    {
        $previousActionForData = [
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

    protected function getPreviousData($input, $prevActionForData)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], $prevActionForData);

        $jsonRaw = json_decode($gatewayPayment['raw']);
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

        unset($data['data']['enqinfo']['Key']);

        unset($data['terminal']);

        unset($data['Key']);

        unset($data['Card']['number']);

        return $data;
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $amount    = $input['payment']['amount'];
        $bank      = $input['payment']['gateway'];

        $gatewayPayment->setAction($this->action);
        $gatewayPayment->setAmount($amount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setBank($bank);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes, $input)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $refundId  = $input['refund']['id'];
        $refundAmount = $input['refund']['amount'];
        $bank      = $input['payment']['gateway'];

        $gatewayPayment->setAction($this->action);
        $gatewayPayment->setRefundId($refundId);
        $gatewayPayment->setAmount($refundAmount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setBank($bank);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function updateGatewayPaymentEntity(
        BaseEntity $gatewayPayment,
        array $attributes,
        bool $mapped = true)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }


    protected function getPaymentToVerify(Verify $verify)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $verify->input['payment']['id'], Action::PAY_VERIFY);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }
}