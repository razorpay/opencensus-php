<?php


namespace RZP\Services\Mock\NbPlus;

use App;
use Requests_Response;

use RZP\Services\NbPlus\Emandate as EmandataBase;

class Emandate extends EmandataBase
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
        if ($this->transactionType === self::DEBIT)
        {
            return [
                'response' => [
                    'data' => [
                        'gateway_reference_id' => '1234',
                        'bank_reference_id'    => '1234',
                        'gateway_status'       => true,
                    ]
                ],
                'error' => null
            ];
        }
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

    protected function callback($input)
    {
        $returnData = [
            'response' => [
                'data' => [
                    'gateway_status'           => true,
                    'recurring_status'         => 'confirmed',
                    'gateway_token'            => str_random(),
                    'recurring_failure_reason' => null,
                ]
            ],
            'error' => null
        ];

        if ($input['input']['payment']['amount'] > 0)
        {
            $returnData['response']['data']['bank_reference_id'] = '1234';
        }

        return $returnData;
    }

    protected function verify($input)
    {
        return [
            'response' => [
                'data' => [
                    'gateway_reference_id' => '1234',
                    'bank_reference_id'    => '1234',
                    'gateway_token'        => str_random(),
                    'gateway_status'       => true,
                ]
            ],
            'error' => null
        ];
    }

    protected function authorizeFailed($input)
    {
        return [
            'response' => [
                'data' => [
                    'gateway_reference_id' => '1234',
                    'recurring_status'     => 'confirmed',
                    'gateway_token'        => str_random(),
                    'gateway_status'       => true,
                ]
            ],
            'error' => null
        ];
    }

    protected function forceAuthorizeFailed($input): array
    {
        return [
            'response' => [
                'data' => [
                    'gateway_reference_id' => '1234',
                    'recurring_status'     => 'confirmed',
                    'gateway_token'        => str_random(),
                    'gateway_status'       => true,
                ]
            ],
            'error' => null
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
