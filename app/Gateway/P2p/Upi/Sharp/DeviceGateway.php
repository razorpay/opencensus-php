<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;

class DeviceGateway extends Gateway implements Contracts\DeviceGateway
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
        $response->setData([
            'token'    => $this->input->get('register_token')->get('token'),
            'device_data' => [
                'contact'      => '919876543210',
                'gateway_data' => [
                    'gateway_device_id' => str_random(16),
                ],
            ],
        ]);

        return $response;
    }

    public function refreshClToken(Response $response)
    {
        $response->setData([
            'cl'    => [
                'token'     => 'I_AM_REFRESTED_TOKEN',
                'payload'   => '<payload>And_i_am_refreshed_payload</payload>'
            ],
        ]);

        return $response;
    }

    public function deregister(Response $response)
    {
        $response->setData([
            'success' => true,
        ]);

        return $response;
    }
}
