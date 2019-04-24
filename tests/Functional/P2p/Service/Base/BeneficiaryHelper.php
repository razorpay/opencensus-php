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
            'handle'       => 'razorhdfc'
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
        $this->validationJsonSchemaPath = 'beneficiary/create';

        $request = $this->request('beneficiaries');

        $default = [
            'type'             => 'vpa',
            'beneficiary_name' => 'beneficiary_1',
            'address'          => 'beneficiary_1@example',
            'save'             => true
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetch()
    {
        $this->validationJsonSchemaPath = 'beneficiary/fetch_all';

        $request = $this->request('beneficiaries');

        $this->get($request);
    }

    protected function validateRequest()
    {
        $this->validationJsonSchemaPath = 'beneficiary/validate';

        $request = $this->request('beneficiaries/validate');

        return $request;
    }
}
