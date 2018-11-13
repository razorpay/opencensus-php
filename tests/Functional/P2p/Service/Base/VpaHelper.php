<?php

namespace RZP\Tests\P2p\Service\Base;

class VpaHelper extends P2pHelper
{
    public function createVpa(array $content = [])
    {
        $this->validationJsonSchemaPath = 'vpa/create';

        $request = $this->request('vpa');

        $default = [
            'address'         => 'random@razorhdfc',
            'bank_account_id' => 'ba_9cWHVXVPkAZZQX'
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function assignBankAccount($vpaId, $bankId)
    {
        $this->validationJsonSchemaPath = 'vpa/create';

        $request = $this->request('vpa/%s/assign/%s',[$vpaId, $bankId]);

        $default = [
            'bank_account_id' => $bankId
        ];

        $this->content($request, $default);

        return $this->post($request);
    }

    public function checkAvailability()
    {
        $this->validationJsonSchemaPath = 'vpa/availability';

        $request = $this->request('vpa/available');

        $default = [
            'address'   => 'random@razorhdfc',
        ];

        $this->content($request, $default);

        return $this->post($request);
    }

    public function deleteVpa(string $vpaId)
    {
        $this->validationJsonSchemaPath = 'vpa/delete';

        $request = $this->request('vpa/%s', [$vpaId]);

        return $this->delete($request);
    }

    public function fetchVpa(string $vpaId)
    {
        $this->validationJsonSchemaPath = 'vpa/create';

        $request = $this->request('vpa/%s', [$vpaId]);

        return $this->get($request);
    }

    public function fetchAllVpa()
    {
        $this->validationJsonSchemaPath = 'vpa/fetch_all';

        $request = $this->request('vpa');

        return $this->get($request);
    }
}
