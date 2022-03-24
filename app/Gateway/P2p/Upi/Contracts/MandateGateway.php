<?php

namespace RZP\Gateway\P2p\Upi\Contracts;

use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Base\GatewayInterface;

/**
 * Interface MandateGateway
 * This is the interface for mandate gateway
 * contains all function declaration for sharp gateway mandates
 * @package RZP\Gateway\P2p\Upi\Contracts
 */

interface MandateGateway extends GatewayInterface
{
    public function initiateAuthorize(Response $response);

    public function authorizeMandate(Response $response);
}
