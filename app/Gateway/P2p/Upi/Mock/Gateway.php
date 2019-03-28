<?php

namespace RZP\Gateway\P2p\Upi\Mock;

use RZP\Gateway\P2p;

class Gateway extends P2p\Upi\Gateway
{
    protected $server;

    protected function handleGatewaySwitch(P2p\Base\Gateway $gateway, string $entity)
    {
        parent::handleGatewaySwitch($gateway, $entity);

        // incase mock is set to true, this gateway will be invoked.
        // We are explicitly setting mock to true here as we do not want to
        // give control to any class to change the mock, which could
        // alter the way requests will be sent.
        $gateway->setMock(true);
    }
}
