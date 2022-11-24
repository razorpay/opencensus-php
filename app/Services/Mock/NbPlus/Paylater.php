<?php

namespace RZP\Services\Mock\NbPlus;
use App;
use \WpOrg\Requests\Response;

use RZP\Models\Payment\Gateway;
use RZP\Services\NbPlus\Paylater as PaylaterBase;

class Paylater extends PaylaterBase
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

    public function checkAccount($input)
    {
        return [
            'response' => [
                'data' => [
                    'account_exists' => true
                ]
            ],
            'error' => null
        ];
    }

    public function request(& $content, $action = '')
    {
        return $content;
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function authorize($input)
    {
        $provider = $input['input']['payment']['wallet'];

        if (in_array($provider, Gateway::$redirectFlowProvider, true) === true)
        {
            return [
                'response' => [
                    'data' => [
                        'next' => [
                            'redirect' => [
                                'url'     => $this->app['api.route']->getPublicCallbackUrlWithHash(
                                    $input['input']['payment']['public_id'],
                                    'rzp_test_TheTestAuthKey',
                                    'payment_callback_post'
                                ),
                                'method'  => 'post',
                                'content' => []
                            ]
                        ]
                    ]
                ],
                'error' => null
            ];
        }
        else
        {
            return [
                'response' => [
                    'data' => [
                        'otpSubmitUrl' => $this->app['api.route']->getPublicCallbackUrlWithHash(
                            $input['input']['payment']['public_id'],
                            'rzp_test_TheTestAuthKey',
                            'payment_otp_submit'
                        ),
                        'next' => null
                    ]
                ],
                'error' => null
            ];
        }
    }

    public function callback($input)
    {
        return [
            'response' => [
                'data' => [
                    'entity' => 'payment',
                    'rzp_payment_id' => 'random_pid',
                    'gateway_reference_number'  => '1234',
                    'status' => 'authorized'
                ]
            ],
            'error' => null
        ];
    }


    protected function verify($input)
    {
        return [
            'response' => [
                'data' => [
                    'gateway_status'           => true,
                    'gateway_reference_number' => '1234'
                ]
            ],
            'error' => null
        ];
    }


    protected function makeJsonResponse(array $content)
    {
        $response = new \WpOrg\Requests\Response();

        $response->headers = ['Content-Type' => 'application/json', 'Cache-Control' => 'no-cache'];

        $response->status_code = 200;

        $response->body = json_encode($content);

        return $response;
    }

}
