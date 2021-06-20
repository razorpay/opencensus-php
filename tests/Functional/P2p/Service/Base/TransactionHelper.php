<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class TransactionHelper extends P2pHelper
{
    public function initiatePay(array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_authorize';

        $request = $this->request('transactions/pay/initiate');

        $default = [
            'amount'        => 100,
            'currency'      => 'INR',
            'description'   => 'Initiate Pay Test',
            'payer'         => [
                'id'    => $this->fixtures->vpa(Fixtures::DEVICE_1)->getPublicId(),
                'type'  => 'vpa'
            ],
            'payee'         => [
                'id'    => $this->fixtures->vpa(Fixtures::DEVICE_2)->getPublicId(),
                'type'  => 'vpa'
            ],
         ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiatePayToBankAccount(array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_authorize';

        $request = $this->request('transactions/pay/initiate');

        $default = [
            'amount'        => 100,
            'currency'      => 'INR',
            'description'   => 'Initiate Pay Test',
            'payer'         => [
                'id'    => $this->fixtures->vpa(Fixtures::DEVICE_1)->getPublicId(),
                'type'  => 'vpa'
            ],
            'payee'         => [
                'id'    => $this->fixtures->bankAccount(Fixtures::DEVICE_2)->getPublicId(),
                'type'  => 'bank_account'
            ],
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateCollect(array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_authorize';

        $request = $this->request('transactions/collect/initiate');

        $default = [
            'amount'        => 100,
            'currency'      => 'INR',
            'description'   => 'Initiate Collect Test',
            'expire_at'     => time() + 1000,
            'payer'         => [
                'id'    => $this->fixtures->vpa(Fixtures::DEVICE_2)->getPublicId(),
                'type'  => 'vpa'
            ],
            'payee'         => [
                'id'    => $this->fixtures->vpa(Fixtures::DEVICE_1)->getPublicId(),
                'type'  => 'vpa'
            ],
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateAuthorize(string $id)
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_authorize';

        $request = $this->request('transactions/%s/authorize/initiate', [$id]);

        return $this->post($request);
    }

    public function authorizeTransaction(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/fetch';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateReject(string $id, array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_authorize';

        $request = $this->request('transactions/%s/reject/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function rejectTransaction(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/fetch';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetchAll(array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/fetch_all';

        $request = $this->request('transactions');

        $this->content($request, [], $content);

        return $this->get($request);
    }

    public function fetch(string $id)
    {
        $this->validationJsonSchemaPath = 'transaction/fetch';

        $request = $this->request('transactions/%s', [$id]);

        return $this->get($request);
    }

    public function raiseConcern(string $transactionId, array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/raise_concern';

        $request = $this->request('concerns/transactions/%s', [$transactionId]);

        $default = [
            'comment' => 'Raising a concern'
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function concernStatus(string $transactionId, array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/raise_concern';

        $request = $this->request('concerns/transactions/%s/status', [$transactionId]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetchAllConcerns(array $content)
    {
        $this->validationJsonSchemaPath = 'transaction/fetch_all_concerns';

        $request = $this->request('concerns/transactions?');

        $default = [];

        $this->content($request, $default, $content);

        return $this->get($request);
    }
}
