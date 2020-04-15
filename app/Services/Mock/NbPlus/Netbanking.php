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

    public function fetchMultiple(string $entityName, array $input): array
    {
        return [];
    }

    public function fetch(string $entityName, string $id, $input)
    {
        return [];
    }

    public function fetchNetbankingData(array $request)
    {
        $response = [
          'count'  => 1,
          'entity' => 'collection',
          'items'  => []
        ];

        foreach ($request['payment_ids'] as $paymentId)
        {
            $response['items'][$paymentId] = [
                'gateway_transaction_id' => str_random(),
                'bank_transaction_id'    => str_random()
            ];
        }

        return $response;
    }

    protected function authorize($input)
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

    protected function callback($input)
    {
        return [
            'response' => [
                'data' => [
                    'gateway_reference_number' => '1234'
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
