<?php

namespace RZP\Services\Mock;

use App;
use RZP\Services\NbPlusPaymentService as BaseNbPlusPaymentService;

class NbPlusPaymentService extends BaseNbPlusPaymentService
{
    public function sendRequest(string $method, string $url, array $input = []): array
    {
        $action = explode('/', $url)[1];

        $this->request($input, $action);

        $response = $this->$action($input);

        $this->content($response, $action);

        return $response;
    }

    public function fetchMultiple(string $entityName, array $input): array
    {
        return [];
    }

    public function fetch(string $entityName, string $id, $input)
    {
        return [];
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

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function request(& $content, $action = '')
    {
        return $content;
    }
}
