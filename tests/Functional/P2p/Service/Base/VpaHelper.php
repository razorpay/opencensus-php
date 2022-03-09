<?php

namespace RZP\Tests\P2p\Service\Base;

class VpaHelper extends P2pHelper
{
    public function fetchHandles()
    {
        $this->validationJsonSchemaPath = 'vpa/list_handles';
        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('handles');

        $this->resetContexts();

        return $this->get($request);
    }

    public function intiateCreateVpa(array $content = [])
    {
        $this->validationJsonSchemaPath = 'vpa/initiate_add';

        $request = $this->request('vpa/initiate');

        $default = [
            'username'        => 'random',
            'bank_account_id' => $this->fixtures->bank_account->getPublicId(),
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function createVpa(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'vpa/add';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function assignBankAccount($vpaId, $bankId)
    {
        $this->validationJsonSchemaPath = 'vpa/add';

        $request = $this->request('vpa/%s/assign',[$vpaId]);

        $default = [
            'bank_account_id' => $bankId,
        ];

        $this->content($request, $default);

        return $this->post($request);
    }

    public function assignBankAccountCallback($callback, $content)
    {
        $this->validationJsonSchemaPath = 'vpa/add';

        $request = $this->request($callback);

        $default = [
            'sdk'              => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function checkAvailability(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'vpa/availability';

        $request = $this->request($callback);

        $default = [
            'sdk'   => [],
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function deleteVpa(string $vpaId)
    {
        $this->validationJsonSchemaPath = 'vpa/delete';

        $request = $this->request('vpa/%s', [$vpaId]);

        return $this->delete($request);
    }

    public function setDefault(string $vpaId)
    {
        $this->validationJsonSchemaPath = 'vpa/add';

        $request = $this->request('vpa/%s/default', [$vpaId]);

        return $this->post($request);
    }

    public function fetchVpa(string $vpaId)
    {
        $this->validationJsonSchemaPath = 'vpa/add';

        $request = $this->request('vpa/%s', [$vpaId]);

        return $this->get($request);
    }

    public function fetchAllVpa(array $content = [])
    {
        $this->validationJsonSchemaPath = 'vpa/fetch_all';

        $request = $this->request('vpa');

        $this->content($request, [], $content);

        return $this->get($request);
    }

    public function initiateCheckVpaAvailable(array $content = [])
    {
        $this->setCustomerInContext(true);

        $this->validationJsonSchemaPath = 'vpa/initiate_available';

        $request = $this->request('vpa/available/initiate');

        $default = [
            'username'        => 'random',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
