<?php

namespace RZP\Tests\P2p\Service\Base;

class BankAccountHelper extends P2pHelper
{
    public function fetchBanks()
    {
        $this->validationJsonSchemaPath = 'bank_account/list_banks';

        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('banks');

        $this->resetContexts();

        return $this->get($request);
    }

    public function initiateRetrieve(string $bankId)
    {
        $this->validationJsonSchemaPath = 'bank_account/initiate_retrieve';

        $request = $this->request('bank_accounts/retrieve/%s/initiate', [$bankId]);

        return $this->post($request);
    }

    public function retrieve(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'bank_account/retrieve';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetchAll()
    {
        $this->validationJsonSchemaPath = 'bank_account/retrieve';

        $request = $this->request('bank_accounts');

        return $this->get($request);
    }

    public function fetch(string $bankId)
    {
        $this->validationJsonSchemaPath = 'bank_account/fetch';

        $request = $this->request('bank_accounts/%s', [$bankId]);

        return $this->get($request);
    }

    public function initiateSetUpiPin(string $bankAccountId, array $content = [])
    {
        $this->validationJsonSchemaPath = 'bank_account/initiate_set_upi_pin';

        $request = $this->request('bank_accounts/%s/upipin/initiate', [$bankAccountId]);

        $default = [
            'action'    => 'set',
            'card'      => [
                'last6'         => '666666',
                'expiry_month'  => '1',
                'expiry_year'   => '99'
            ],
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function setUpiPin(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'bank_account/set_upi_pin';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateFetchBalance(string $bankId)
    {
        $this->validationJsonSchemaPath = 'bank_account/initiate_fetch_balance';

        $request = $this->request('bank_accounts/%s/balance/initiate', [$bankId]);

        return $this->post($request);
    }

    public function fetchBalance(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'bank_account/fetch_balance';

        $request = $this->request($callback);

        $default = [
            'sdk' => [],
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
