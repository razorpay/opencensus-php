<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Gateway\P2p\Upi\Axis\Library\Action;
use RZP\Gateway\P2p\Upi\Axis\Library\Fields;
use RZP\Gateway\P2p\Upi\Axis\Library\Request;
use RZP\Gateway\P2p\Upi\Axis\Library\Requests\Device;

class DeviceGateway extends Gateway implements Contracts\DeviceGateway
{

    public function startVerification(Response $response)
    {

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

    public function initiateVerification(Response $response)
    {
        $request = $this->initiateSdkRequest(Action::BIND_DEVICE, Device::MAP);

        $attributes = [
            Fields::SIM_ID => $this->input->get('register_token')->get(RegisterToken\Entity::DEVICE_DATA)
                                                                            ['simSlot']
        ];

        $request->merge($attributes);

        $request->finish();

        $response->setData([
            'request'       => $request->toArray(),
            'callback'      => [
                'token'         => $this->input->get('register_token')->get('token'),
            ]
        ]);
    }
}
