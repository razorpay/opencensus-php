<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

interface VpaGateway extends GatewayInterface
{
    public function add(Response $response);

    public function assignBankAccount(Response $response);

    public function checkAvailability(Response $response);

    public function delete(Response $response);
}
