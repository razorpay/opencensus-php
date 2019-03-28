<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;

class BankAccountGateway extends Gateway implements Contracts\BankAccountGateway
{
    public function initiateRetrieve(Response $response)
    {
        $request = new Request();

        $request->setUrl(null);
        $request->setAction('retrieve');

        $response->setRequest($request);
    }

    public function retrieve(Response $response)
    {
        $bankIfsc = $this->input->get('bank')->get('ifsc');

        $response->setData([
            'bank_id'       => $this->input->get('bank')->get('id'),
            'bank_accounts' => [
                [
                    'ifsc'                  => $bankIfsc . '0000100',
                    'beneficiary_name'      => 'Sharp Customer',
                    'account_number'        => '97531086420',
                    'masked_account_number' => 'xxxxxx86420',
                    'gateway_data'          => [
                        'id'                => 'SRPA97531086420',
                    ],
                    'creds'                 => [
                        [
                            'type'          => 'pin',
                            'sub_type'      => 'upipin',
                            'length'        => 6,
                            'format'        => 'NUM'
                        ],
                        [
                            'type'          => 'pin',
                            'sub_type'      => 'atmpin',
                            'length'        => 4,
                            'format'        => 'ALPHANUM'
                        ]
                    ]
                ],
            ],
        ]);
    }

    public function initiateSetUpiPin(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $request = new Request();

        $request->setSdk('npci');

        $request->setContent([
            'id'    => 'SRP' .str_random(32),
            'note'  => 'Set UPI PIN',
        ]);
        $request->setAction('set_upi_pin');

        $response->setRequest($request);
    }

    public function setUpiPin(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $response->setData([
            'id'    => $bankAccount->get('id'),
        ]);
    }

    public function initiateFetchBalance(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $request = new Request();

        $request->setSdk('npci');

        $request->setContent([
            'id'    => 'SRP' .str_random(32),
            'note'  => 'Fetch Balance',
        ]);
        $request->setAction('fetch_balance');

        $response->setRequest($request);
    }

    public function fetchBalance(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $response->setData([
            'id'            => $bankAccount->get('id'),
            'response'      => [
                'balance'   => 2928200,
                'currency'  => 'INR',
            ]
        ]);
    }
}
