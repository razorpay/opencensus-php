<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function fetchHandles(array $input): array
    {
        $this->initialize(Action::FETCH_HANDLES, $input);

        return [];
    }

    public function add(array $input): array
    {
        $this->initialize(Action::ADD, $input);

        return [
            'id'              => 'vpa_AagzIzN8Hgp3wU',
            'entity'          => 'vpa',
            'address'         => $input['address'],
            'username'        => 'abcd',
            'handle'          => 'razorhdfc',
            'bank_account_id' => $input['bank_account_id'],
            'created_at'      => time()
        ];
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        return [
            'entity'  => 'collection',
            'count'   => 2,
            'items'   => [
                [
                    'id'              => 'vpa_8d4fWzd6fwz4qP',
                    'entity'          => 'vpa',
                    'address'         => 'abcd@razorhdfc',
                    'username'        => 'abcd',
                    'handle'          => 'razorhdfc',
                    'bank_account_id' => 'ba_9cWHVXVPkAZZQZ',
                    'created_at'      => time()
                ],
                [
                    'id'              => 'vpa_AagzIzN8Hgp3wU',
                    'entity'          => 'vpa',
                    'address'         => 'abcd@razoricici',
                    'username'        => 'abcd',
                    'handle'          => 'razoricici',
                    'bank_account_id' => 'ba_9cWHVXVPkAZZQZ',
                    'created_at'      => time()
                ]
            ]
        ];
    }

    public function fetch(array $input): array
    {
        $this->initialize(Action::FETCH, $input);

        return [
            'id'              => 'vpa_AagzIzN8Hgp3wU',
            'entity'          => 'vpa',
            'address'         => 'abcd@razorhdfc',
            'username'        => 'abcd',
            'handle'          => 'razorhdfc',
            'bank_account_id' => 'ba_9cWHVXVPkAZZQZ',
            'created_at'      => time()
        ];
    }

    public function assignBankAccount(array $input): array
    {
        $this->initialize(Action::ASSIGN_BANK_ACCOUNT, $input);

        return [
            'id'              => 'vpa_AagzIzN8Hgp3wU',
            'entity'          => 'vpa',
            'address'         => 'abcd@razorhdfc',
            'username'        => 'abcd',
            'handle'          => 'razorhdfc',
            'bank_account_id' => $input['bank_account_id'],
            'created_at'      => time()
        ];
    }

    public function checkAvailability(array $input): array
    {
        $this->initialize(Action::CHECK_AVAILABILITY, $input);

        return [
            'address'      => $input['address'],
            'available'    => true
        ];
    }

    public function delete(array $input): array
    {
        $this->initialize(Action::DELETE, $input);

        return [
            'id'      => 'vpa_AagzIzN8Hgp3wU',
            'deleted' => true
        ];
    }
}
