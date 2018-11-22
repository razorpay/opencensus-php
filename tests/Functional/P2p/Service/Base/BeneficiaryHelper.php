<?php

namespace RZP\Tests\P2p\Service\Base;

class BeneficiaryHelper extends P2pHelper
{
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

    public function validate(array $content = [])
    {
        $this->validationJsonSchemaPath = 'beneficiary/validate';

        $request = $this->request('beneficiaries/validate');

        $default = [
            'address'     => 'customer@razorhdfc'
        ];

        $this->content($request, $default, $content);

        $this->post($request);
    }

    public function fetch()
    {
        $this->validationJsonSchemaPath = 'beneficiary/fetch_all';

        $request = $this->request('beneficiaries');

        $this->get($request);
    }
}
