<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Models\P2p\Preferences\Entity;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\ClientAction;

/**
 * Class file responsible for client gateway interaction
 * Class ClientGateway
 *
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class PreferencesGateway extends Gateway implements Contracts\PreferencesGateway
{
    public function getPreferences(Response $response)
    {
        $response->setData($this->input[Entity::PREFERENCES]);
    }
}
