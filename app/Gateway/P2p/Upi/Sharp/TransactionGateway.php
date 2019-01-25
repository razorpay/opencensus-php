<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;

class TransactionGateway extends Gateway implements Contracts\TransactionGateway
{
    public function initiatePay(Response $response)
    {
        $transactionId = 'SRP' . str_random();

        $response->setData([
            'transaction' => [
                'id'    => $this->input->get('transaction')->get('id'),
            ],
            'upi' => [
                'transaction_id'         => $this->input->get('upi')->get('transaction_id'),
                'network_transaction_id' => $transactionId,
                'gateway_transaction_id' => 'SRP' . $this->input->get('transaction')->get('payer_id'),
            ],
        ]);
    }

    public function initiateCollect(Response $response)
    {
        $transactionId = 'SRP' . str_random();

        $response->setData([
            'transaction' => [
                'id'    => $this->input->get('transaction')->get('id'),
            ],
            'upi' => [
                'transaction_id'         => $this->input->get('upi')->get('transaction_id'),
                'network_transaction_id' => $transactionId,
                'gateway_transaction_id' => 'SRP' . $this->input->get('transaction')->get('payer_id'),
                'rrn'                    => (string) random_integer(12),
            ],
        ]);
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
        $response->setData([]);
    }

    public function authorizeTransaction(Response $response)
    {
        $transactionId = 'SRP' . str_random();

        $response->setData([
            'transaction' => [
                'id'    => $this->input->get('transaction')->get('id'),
            ],
            'upi' => [
                'transaction_id'         => $this->input->get('upi')->get('transaction_id'),
                'network_transaction_id' => $transactionId,
                'gateway_transaction_id' => 'SRP' . $this->input->get('transaction')->get('payer_id'),
                'rrn'                    => (string) random_integer(12),
            ],
        ]);
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
