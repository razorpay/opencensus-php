<?php

namespace RZP\Tests\P2p\Service\Base;

class BeneficiaryHelper extends P2pHelper
{
    public function validateVpa(array $content = [])
    {
        $request = $this->validateRequest();

        $default = [
            'type'         => 'vpa',
            'username'     => 'customer',
            'handle'       => 'razorhdfc',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function validateBankAccount(array $content = [])
    {
        $request = $this->validateRequest();

        $default = [
            'type'              => 'bank_account',
            'account_number'    => '987654321000',
            'ifsc'              => 'HDFC0000001',
            'beneficiary_name'  => 'Razorpay Customer',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function create(array $content = [])
    {
        $this->validationJsonSchemaPath = 'beneficiary/validate';

        $request = $this->request('beneficiaries');

        $default = array_only($content, [
            'validated',
            'type',
            'id',
            'name',
        ]);

        $this->content($request, $default, []);

        return $this->post($request);
    }

    public function fetch()
    {
        $this->validationJsonSchemaPath = 'beneficiary/fetch_all';

        $request = $this->request('beneficiaries');

        return $this->get($request);
    }

    protected function validateRequest()
    {
        $this->validationJsonSchemaPath = 'beneficiary/validate';

        $request = $this->request('beneficiaries/validate');

        return $request;
    }

    public function handle(array $content = [])
    {
        $this->validationJsonSchemaPath = 'beneficiary/handle';

        $request = $this->request('beneficiaries/handle');

        $default = [
            'blocked'  => false,
            'spammed'  => false,
            'type'     => 'vpa',
            'username' => 'customer',
            'handle'   => 'testpsp',
        ];

        $this->content($request, $default, []);

        return $this->post($request);
    }

    public function fetchBlocked()
    {
        $request = $this->request('beneficiaries?blocked=1');

        return $this->get($request);
    }
}
