<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Models\P2p\Device;
use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\Base\PublicCollection;
use RZP\Models\P2p\Device\RegisterToken;

class UpiGateway extends Gateway implements Contracts\UpiGateway
{
    public function initiateGatewayCallback(Response $response)
    {
        $response->setData([
            Device\Entity::REGISTER_TOKEN => [
                'token'         => $this->input['content']['t'] ?? null,
                'device_data'   => [
                    'contact'   => $this->input['content']['c'] ?? null,
                ]
            ],
            Device\Entity::CONTEXT  => [
                'entity'        => Device\Entity::REGISTER_TOKEN,
                'action'        => Device\Action::VERIFICATION_SUCCESS,
            ],
        ]);
    }

    public function gatewayCallback(Response $response)
    {
        $this->input->put(Device\Entity::RESPONSE, [
            Device\Entity::SUCCESS => true,
        ]);

        $response->setData($this->input->toArray());
    }
}
