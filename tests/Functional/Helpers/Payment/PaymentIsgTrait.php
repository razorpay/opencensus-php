<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentIsgTrait
{
    protected function createVirtualAccount()
    {
        $this->ba->privateAuth();

        $request = [
            'url'     => '/virtual_accounts',
            'method'  => 'post',
            'content' => [
                'receiver_types' => 'qr_code'
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }
}