<?php

namespace RZP\Gateway\Mozart;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Mode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    protected $gateway = 'mozart';

    public function authorize(array $input)
    {
        parent::action($input, Action::PAY_INIT);

        $request = $this->getMozartRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        return $response['next']['redirect'] ?? null;
    }

    public function otpGenerate(array $input)
    {
        return $this->authorize($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        return $this->callback($input);
    }

    public function callback(array $input)
    {
        parent::action($input, Action::PAY_VERIFY);

        $gateway = $input['gateway'];

        unset($input['gateway']);

        $input['gateway']['redirect'] = $gateway;

        $request = $this->getMozartRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        if ($input['payment']['gateway'] === Payment\Gateway::BAJAJFINSERV)
        {
            $input['gateway']['pay_verify']['requestid'] = $response['data']['RequestID'];

            $verifyResponse = $this->verify($input);

            return $verifyResponse;
        }

        return $response;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getMozartRequestArray($input);

        $response = $this->sendGatewayRequest($request);

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
        return $this->verify($input);
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

        $verify->verifyResponseContent = $this->sendMozartRequest($input);

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

        $attributes = $this->getMappedAttributes($content['data']);

        $this->updateGatewayPaymentEntity($verify->payment, $attributes);

        return $verify->status;
    }

    protected function verifyNonExistentCase($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $verify->gatewaySuccess = false;

        if (($payment === null) and
            (($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created')))
        {
            $verify->apiSuccess = false;
        }
        else if (($payment['status'] === null) or
            ($payment['status'] !== Payment\Status::AUTHORIZED))
        {
            $verify->apiSuccess = false;
        }
        else if ($payment['status'] === Payment\Status::AUTHORIZED)
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

        if (($input['payment']['status'] !== 'created') and
            ($input['payment']['status'] !== 'failed'))
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

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        return $this->jsonToArray($response->body, true);
    }
}