<?php

namespace RZP\Services\Mock;

use App;
use RZP\Services\NbPlusPaymentService as BaseNbPlusPaymentService;

class NbPlusPaymentService extends BaseNbPlusPaymentService
{
    public function action(string $gateway, string $action, array $input): array
    {
        return $this->$action($gateway, $input);
    }

    public function fetchMultiple(string $entityName, array $input): array
    {
        return [];
    }

    public function fetch(string $entityName, string $id, $input)
    {
        return [];
    }

    protected function authorize($gateway, $input)
    {
        return [
            'data' => [
                'url'     => $this->app['api.route']
                                  ->getPublicCallbackUrlWithHash(
                                                        $input['payment']['public_id'],
                                                        'rzp_test_TheTestAuthKey',
                                                        'payment_callback_post'
                                                       ),
                'method'  => 'post',
                'content' => []
            ]
        ];
    }

    protected function callback($gateway, $input)
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
}
