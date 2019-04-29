<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;

class TransactionGateway extends Gateway implements Contracts\TransactionGateway
{
    public function initiatePay(Response $response)
    {
        $request = new Request();

        $customerVpa = $this->input->get(Transaction\Entity::PAYER)[Vpa\Entity::ADDRESS];

        $payeeVpa = $this->input->get(Transaction\Entity::PAYEE)[Vpa\Entity::ADDRESS];

        $request->setSdk('npci');
        $request->setContent([
            'customerVpa'    => $customerVpa,
            'payeeVpa'       => $payeeVpa
        ]);

        $response->setRequest($request);
    }

    public function initiateCollect(Response $response)
    {
        $request = new Request();

        $customerVpa = $this->input->get(Transaction\Entity::PAYEE)[Vpa\Entity::ADDRESS];

        $payeeVpa = $this->input->get(Transaction\Entity::PAYER)[Vpa\Entity::ADDRESS];

        $request->setSdk('npci');
        $request->setContent([
            'customerVpa'    => $customerVpa,
            'payeeVpa'       => $payeeVpa
        ]);

        $response->setRequest($request);
    }

    public function fetchAll(Response $response)
    {
        $response->setData([]);
    }

    public function fetch(Response $response)
    {
        $response->setData([]);
    }

    public function initiateAuthorize(Response $response)
    {
        $request = new Request();

        $customerVpa = $this->input->get(Transaction\Entity::PAYEE)[Vpa\Entity::ADDRESS];

        $payeeVpa = $this->input->get(Transaction\Entity::PAYER)[Vpa\Entity::ADDRESS];

        $request->setSdk('npci');
        $request->setContent([
            'customerVpa'    => $customerVpa,
            'payeeVpa'       => $payeeVpa
        ]);

        $response->setRequest($request);
    }

    public function authorizeTransaction(Response $response)
    {
        $transactionId = 'SRP' . str_random();

        $response->setData([
            'transaction' => [
                'id'                     => $this->input->get('transaction')->get('id'),
                'internal_status'        => 'completed',
            ],
            'upi' => [
                'transaction_id'         => $this->input->get('upi')->get('transaction_id'),
                'network_transaction_id' => $transactionId,
                'gateway_transaction_id' => 'SRP' . $this->input->get('transaction')->get('payer_id'),
                'rrn'                    => (string) random_integer(12),
            ],
        ]);
    }

    public function initiateReject(Response $response)
    {
        $request = new Request();

        $request->setUrl(null);

        $request->setContent([
            'status' => 'rejecting'
        ]);

        $response->setRequest($request);
    }

    public function reject(Response $response)
    {
        $transactionId = 'SRP' . str_random();

        $response->setData([
            'transaction' => [
                'id'    => $this->input->get('transaction')->get('id'),
            ],
            'success' => true,
        ]);
    }
}
