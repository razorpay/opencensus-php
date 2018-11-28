<?php

namespace RZP\Tests\P2p\Service\Base;

class TransactionHelper extends P2pHelper
{
    public function initiatePay(array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_pay';

        $request = $this->request('transactions/pay/initiate');

        $default = [
            'amount'        => 100,
            'currency'      => 'INR',
            'description'   => 'Initiate Pay Test',
            'payer_id'      => 'vpa_PayerVpa000001',
            'payee_id'      => 'vpa_PayeeVpa000001',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateCollect(array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_collect';

        $request = $this->request('transactions/collect/initiate');

        $default = [
            'amount'        => 100,
            'currency'      => 'INR',
            'description'   => 'Initiate Pay Test',
            'expire_at'     => time() + 1000,
            'payer_id'      => 'vpa_PayerVpa000001',
            'payee_id'      => 'vpa_PayeeVpa000001',
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateAuthorize(string $id)
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_pay';

        $request = $this->request('transactions/%s/authorize/initiate', [$id]);

        return $this->get($request);
    }

    public function authorizeTransaction(string $id, array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_collect';

        $request = $this->request('transactions/%s/authorize', [$id]);

        $default = [
            'cl.creds' => [
                [
                    'code'     => 'NPCI',
                    'ki'       => '20150806',
                    'string'   => 'SomeVerySercetString',
                    'sub_type' => 'UPIPIN',
                    'type'     => 'PIN',
                ],
            ],
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function rejectTransaction(string $id, array $content = [])
    {
        $this->validationJsonSchemaPath = 'transaction/initiate_collect';

        $request = $this->request('transactions/%s/reject', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetchAll()
    {
        $this->validationJsonSchemaPath = 'transaction/fetch_all';

        $request = $this->request('transactions');

        return $this->get($request);
    }

    public function fetch(string $id)
    {
        $this->validationJsonSchemaPath = 'transaction/fetch';

        $request = $this->request('transactions/%s', [$id]);

        return $this->get($request);
    }
}
