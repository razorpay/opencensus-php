<?php

namespace RZP\Tests\P2p\Service\Base;

class CustomerHelper extends P2pHelper
{
    public function sendVerificationStart(array $content = [])
    {
        $this->validationJsonSchemaPath = 'customer/start_verification';

        $request = $this->request('customers/verification/start');

        $default = [
            'handle' => 'razorsharp'
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetchVerificationStatus(string $token)
    {
        $this->validationJsonSchemaPath = 'customer/verification_status';

        $request = $this->request('customers/verification/%s', [$token]);

        return $this->get($request);
    }

    public function postCreateCustomer(array $content = [])
    {
        $this->validationJsonSchemaPath = 'customer/create';

        $request = $this->request('customers');

        $default = [
            'name'          => 'Some Name',
            'email'         => 'some@email.com',
            'notes'         => ['b' => 'a'],
            'identifier'    => 'merchant_customer_id',
            'token'         => 'p2p_verified_token',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
