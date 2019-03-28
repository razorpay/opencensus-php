<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface DeviceGateway extends GatewayInterface
{
    public function initiateVerification(Response $response);

    public function verification(Response $response);

    public function initiateGetToken(Response $response);

    public function getToken(Response $response);

    public function deregister(Response $response);
}
