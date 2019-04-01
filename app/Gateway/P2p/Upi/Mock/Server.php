<?php

namespace RZP\Gateway\P2p\Upi\Mock;

use RZP\Gateway\P2p\Base;
use RZP\Gateway\Base\Mock;

class Server extends Mock\Server
{
    /**
     * @var Base\Mock\Server
     */
    protected $gatewayServer;

    public function setGatewayServer(Base\Mock\Server $server)
    {
        $this->gatewayServer = $server;
    }

    public function hasGatewayServer()
    {
        return ($this->gatewayServer instanceof Base\Mock\Server);
    }

    public function getGatewayServer(): Base\Mock\Server
    {
        return $this->gatewayServer;
    }
}
