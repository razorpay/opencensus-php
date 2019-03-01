<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Gateway\P2p\Upi;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;

class DeviceGateway extends Upi\Gateway implements Contracts\DeviceGateway
{
    public function startVerification(Response $response)
    {
        $response->setData([
            'token'         => $this->input->get('register_token')->get('token'),
            'device_data'   => [
                'gateway_data' => [
                    'verify_code'   => str_random(16),
                ],
            ],
            'response'      => [
                'action'            => 'verify',
                'method'            => 'sms',
                'sms_destination'   => '+919876543210',
                'sms_content'       => 'VERIFY ME NOW'
            ]
        ]);
    }

    public function getVerificationStatus(Response $response)
    {
    }

    public function refreshClToken(Response $response)
    {

    }

    public function deregister(Response $response)
    {

    }
}
