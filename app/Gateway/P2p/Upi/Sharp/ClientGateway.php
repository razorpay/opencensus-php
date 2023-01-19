<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Models\P2p\Client\Entity;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\ClientAction;

/**
 * Class file responsible for client gateway interaction
 * Class ClientGateway
 *
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class ClientGateway extends Gateway implements Contracts\ClientGateway
{
    protected $actionMap = ClientAction::MAP;

    public function getGatewayConfig(Response $response)
    {
        $response->setData([
           Entity::GATEWAY_CONFIG => [
                Fields::MCC => '7298'
           ],
           Entity::TOKEN => [
                Fields::GATEWAY_TOKEN => 'test token'
           ],
       ]);
    }
}
