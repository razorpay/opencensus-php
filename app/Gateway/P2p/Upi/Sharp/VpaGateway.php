<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;

class VpaGateway extends Gateway implements Contracts\VpaGateway
{
    public function initiateAdd(Response $response)
    {
        $request = new Request();

        $request->setUrl(null);

        $request->setContent([
            'username'          => $this->input->get('username')
        ]);

        $response->setRequest($request);
    }

    public function add(Response $response)
    {
        $response->setData([
            'vpa'   => [
                'username'      => $this->input->get('username'),
                'handle'        => $this->context->handleCode(),
                'gateway_data'  => [
                    'id'                => 'SRPVPA' . random_integer(10),
                    'bank_account_id'   => 'SRPBANK' . random_integer(10),
                ]
            ],
            'bank_account'  => [
                'id'    => $this->input->get('bank_account')->get('id')
            ]
        ]);
    }

    public function assignBankAccount(Response $response)
    {
        $response->setData([
            'vpa'   => [
                'id'    => $this->input->get('vpa')->get('id'),
            ],
            'bank_account'  => [
                'id'    => $this->input->get('bank_account')->get('id'),
            ]
        ]);
    }

    public function initiateCheckAvailability(Response $response)
    {
        $this->initiateAdd($response);
    }

    public function checkAvailability(Response $response)
    {
        $response->setData([
            'available'     => true,
            'username'      => $this->input->get('username'),
            'handle'        => $this->context->handleCode(),
        ]);
    }

    public function delete(Response $response)
    {
        $response->setData([
            'success'   => true,
            'vpa'   => [
                'id'    => $this->input->get('vpa')->get('id'),
            ],
        ]);
    }
}
