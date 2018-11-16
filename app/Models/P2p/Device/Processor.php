<?php

namespace RZP\Models\P2p\Device;

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
            'auth_token'    => 'token',
            'expire_at'     => time() + 900,
        ];
    }

    public function refreshClToken(array $input): array
    {
        $this->initialize(Action::REFRESH_CL_TOKEN, $input);

        return [
            'contact'        => '+919876543210',
            'handle'         => 'razorsharp',
            'cl.token'       => '52000002000100040006',
            'cl.payload'     => 'AUnhIkGYnGBK==',
            'refreshed_at'   => time()
        ];
    }

    public function deregister(array $input): array
    {
        $this->initialize(Action::DEREGISTER, $input);

        return [
            'id'               => $input['id'],
            'success'          => true,
        ];
    }
}
