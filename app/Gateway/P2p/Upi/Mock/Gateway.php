<?php

namespace RZP\Gateway\P2p\Upi\Mock;

use RZP\Gateway\P2p;

class Gateway extends P2p\Upi\Gateway
{
    protected $server;

    protected function handleGatewaySwitch(P2p\Base\Gateway $gateway, string $entity)
    {
        parent::handleGatewaySwitch($gateway, $entity);

        $gateway->setMock(true);
    }
}
