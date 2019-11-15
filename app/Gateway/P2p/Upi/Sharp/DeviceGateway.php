<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Models\P2p\Device\Entity;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;


class DeviceGateway extends Gateway implements Contracts\DeviceGateway
{
    public function initiateVerification(Response $response)
    {
        $request = new Request();

        $request->setDestination('+919876543210');
        $request->setContent('VERIFY ME NOW');
        $request->setAction('verify');
        $request->setCallback([
            'verify_code'   => str_random(16),
        ]);

        $response->setRequest($request);
    }

    public function verification(Response $response)
    {
        $response->setData([
            'token'    => $this->input->get('register_token')->get('token'),
            'device_data' => [
                'contact'      => $this->input->get('sdk')->get('contact', '919876543210'),
                'gateway_data' => [
                    'gateway_device_id' => str_random(16),
                ],
            ],
        ]);
    }

    public function initiateGetToken(Response $response)
    {
        $request = new Request();

        $request->setSdk('npci');
        $request->setContent([
            'token'     => 'I_AM_REFRESTED_TOKEN',
            'payload'   => '<payload>And_i_am_refreshed_payload</payload>'
        ]);
        $request->setAction('saveToken');
        $request->setCallback([
            'verify_code'   => str_random(16),
        ]);

        $response->setRequest($request);
    }

    public function getToken(Response $response)
    {
        $response->setData([
            Entity::DEVICE_TOKEN => [
                Entity::ID            => $this->getContextDeviceToken()->get(Entity::ID),
                'gateway_data' => [
                    'token'     => 'I_AM_REFRESTED_TOKEN',
                    'payload'   => '<payload>And_i_am_refreshed_payload</payload>'
                ],
            ]
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
