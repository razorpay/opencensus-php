<?php

namespace RZP\Services\Mock\NbPlus;

use App;
use Requests_Response;

use RZP\Services\NbPlus\Netbanking as NetbankingBase;

class Netbanking extends NetbankingBase
{
    public function sendRawRequest($request)
    {
        $action  = camel_case(explode('/', $request['url'])[1]);

        $content = $request['content'];

        $this->request($content, $action);

        $response = $this->$action($content);

        $this->content($response, $action);

        return $this->makeJsonResponse($response);
    }

    protected function authorize($input)
    {
        return [
            'data' => [
                'url'     => $this->app['api.route']
                                  ->getPublicCallbackUrlWithHash(
                                                        $input['input']['payment']['public_id'],
                                                        'rzp_test_TheTestAuthKey',
                                                        'payment_callback_post'
                                                       ),
                'method'  => 'post',
                'content' => []
            ]
        ];
    }

    protected function callback($input)
    {
        return [
            'data' => [
                'acquirer' => [
                    'reference1' => '1234'
                ],
                'two_factor_auth' => 'unavailable'
            ]
        ];
    }

    protected function verify($input)
    {
        return [
            'data' => [
                'gateway_success' => true,
                'acquirer' => [
                    'reference1' => '1234'
                ],
            ]
        ];
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function request(& $content, $action = '')
    {
        return $content;
    }

    protected function makeJsonResponse(array $content)
    {
        $response = new Requests_Response();

        $response->headers = ['Content-Type' => 'application/json', 'Cache-Control' => 'no-cache'];

        $response->status_code = 200;

        $response->body = json_encode($content);

        return $response;
    }
}
