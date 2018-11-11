<?php

namespace RZP\Models\P2p\Customer;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function startVerification(array $input): array
    {
        $this->initialize(Action::START_VERIFICATION, $input);

        return [
            'handle'            => $input['handle'],
            'token'             => str_random(10),
            'action'            => 'verify',
            'method'            => 'sms',
            'sms_destination'   => '+919876543210',
            'sms_content'       => 'VERIFY ME'
        ];
    }

    public function getVerificationStatus(array $input): array
    {
        $this->initialize(Action::GET_VERIFICATION_STATUS, $input);

        return [
            'status'        => 'pending',
            'status_url'    => url($input['token']),
            'expire_at'     => time() + 900,
        ];
    }

    public function create(array $input): array
    {
        $this->initialize(Action::CREATE, $input);

        return [
            'id'            => 'cust_bahuthuyehumen',
            'contact'       => '+919876543210',
            'email'         => $input['email'],
            'active'        => true,
            'notes'         => $input['notes'],
            'created_at'    => time(),
        ];
    }

    public function delete(array $input): array
    {
        $this->initialize(Action::DELETE, $input);

        return [
            'id'            => 'cust_bahuthuyehumen',
            'success'       => true,
        ];
    }
}
