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
            $input['payment']['id'], 'pay_init');

        $this->gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $response, true);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        if ($input['payment']['gateway'] === Payment\Gateway::BAJAJFINSERV)
        {
            $input['gateway']['pay_verify']['RequestID'] = $response['data']['RequestID'];

            $verifyResponse = $this->verify($input);

            return $verifyResponse;
        }

        if ($input['payment']['gateway'] === Payment\Gateway::NETBANKING_SIB)
        {
            $this->assertPaymentId($input['payment']['id'], $response['data']['payment_id']);

            $this->assertAmount($input['payment']['amount'], (int) $response['data']['amount']);

            $this->verifyCallback($input, $response);
        }

        return $response;
    }

    public function verifyCallback($input, $callbackResponse)
    {
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

        $prevStep = $this->getPreviousStep($input['payment']['gateway']);

        if ($prevStep != null)
        {
            $input['gateway'][$prevStep] = $this->getPreviousData($input, $prevStep);
        }

        if ($this->action === Action::PAY_INIT and $input['payment']['gateway'] === Payment\Gateway::NETBANKING_SIB)
        {
            $input['gateway']['payment']['callbackUrl'] = $input['callbackUrl'];
        }

        if (($this->action === Action::VERIFY) and
            ($input['payment']['gateway'] === Payment\Gateway::NETBANKING_SIB) and
            (isset($input['gateway'][$prevStep]['bank_payment_id']) === false))

        {
            $input['gateway'][$prevStep]['bank_payment_id'] = '0';
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
            Payment\Gateway::NETBANKING_SIB => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_VERIFY
            ],
        ];

        return $previousActionForData[$gateway][$this->action];
    }

    protected function getPreviousData($input, $prevActionForData)
    {
        if ($this->action === Action::VERIFY)
        {
            $gatewayPayment = $this->getMozartEntityForVerify($input['payment']['id']);
        }
        else
        {
            $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'], $prevActionForData);
        }

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

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $amount    = $input['payment']['amount'];
        $gateway   = $input['payment']['gateway'];

        $gatewayPayment->setAction($this->action);
        $gatewayPayment->setAmount($amount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

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
        $gateway      = $input['payment']['gateway'];

        $gatewayPayment->setAction($this->action);
        $gatewayPayment->setRefundId($refundId);
        $gatewayPayment->setAmount($refundAmount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

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
        $gatewayPayment = $this->getMozartEntityForVerify(
            $verify->input['payment']['id']);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function getMozartEntityForVerify($paymentId)
    {
        $prevActionsForVerify = [Action::PAY_INIT, Action::PAY_VERIFY, Action::VERIFY];

        return $this->repo->findByPaymentIdAndActionsOrFail(
            $paymentId, $prevActionsForVerify);
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
}
