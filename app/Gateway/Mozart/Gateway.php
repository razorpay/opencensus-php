<?php

namespace RZP\Gateway\Mozart;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Mode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'bajajfinserv';

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

        if ($input['payment']['gateway'] === Payment\Gateway::BAJAJ)
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

    public function verify(array $input)
    {
        parent::verify($input);

        $request = $this->getMozartRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        return $response;
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