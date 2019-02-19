<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;

class BankAccountGateway extends Gateway implements Contracts\BankAccountGateway
{
    public function retrieve(Response $response)
    {
        $bank = $this->input->get('bank')->get('ifsc');

        $response->setData([
            'bank'          => $bank,
            'bank_accounts' => [
                [
                    'ifsc'                  => $bank . '0000100',
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

        $response->setData([
            'bank'          => $bankAccount->get('bank'),
            'bank_account'  => [
                'id'        => $bankAccount->get('id'),
                'gateway_data'          => [
                    'id'                => 'SRPA000000001',
                ],
            ],
            'txn'   => [
                'id'    => 'SRP' .str_random(32),
                'note'  => 'Set UPI PIN',
            ]
        ]);
    }

    public function setUpiPin(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $response->setData([
            'bank'          => $bankAccount->get('bank'),
            'bank_account'  => [
                'id'        => $bankAccount->get('id'),
            ],
            'txn'           => $this->input->get('request')->get('txn'),
        ]);
    }

    public function initiateFetchBalance(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $response->setData([
            'bank'          => $bankAccount->get('bank'),
            'bank_account'  => [
                'id'        => $bankAccount->get('id'),
                'gateway_data'          => [
                    'id'                => 'SRPA000000001',
                ],
            ],
            'txn'   => [
                'id'    => 'SRP' .str_random(32),
                'note'  => 'Balance Enquiry',
            ]
        ]);
    }

    public function fetchBalance(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $response->setData([
            'bank'          => $bankAccount->get('bank'),
            'bank_account'  => [
                'id'        => $bankAccount->get('id'),
            ],
            'txn'           => $this->input->get('request')->get('txn'),
            'response'      => [
                'balance'   => 2928200,
                'currency'  => 'INR',
            ]
        ]);
    }
}
