<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function create(array $input): array
    {
        $this->initialize(Action::CREATE, $input);

        return [
            'id'               => 'vpa_8zIfY8quFElCbH',
            'entity'           => 'vpa',
            'beneficiary_name' => $input['beneficiary_name'],
            'address'          => $input['address'],
            'username'         => 'beneficiary',
            'handle'           => 'razorhdfc',
            'created_at'       => time()
        ];
    }

    public function validate(array $input): array
    {
        $this->initialize(Action::VALIDATE, $input);

        return [
            'address'          => $input['address'],
            'beneficiary_name' => 'User',
            'validated'        => true
        ];
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        return [
            'entity'   => 'collection',
            'count'    => 2,
            'items'    => [
                [
                    'id'                    => 'vpa_8zIfY8quFElCbH',
                    'entity'                => 'vpa',
                    'beneficiary_name'      => 'Beneficiary Name',
                    'address'               => 'beneficiary@razorhdfc',
                    'username'              => 'beneficiary',
                    'handle'                => 'razorhdfc',
                    'created_at'            => time()
                ],
                [
                    'id'                    => 'ba_8zIfY7hSkCF8wr',
                    'entity'                => 'bank_account',
                    'beneficiary_name'      => 'Beneficiary Name',
                    'masked_account_number' => '*********1234',
                    'ifsc_code'             => 'RAZ00000001',
                    'bank_name'             => 'Razorpay',
                    'address'               => '100010001000@RAZ00000001.ifsc.npci',
                    'created_at'            => time()
                ]
            ]
        ];
    }
}
