<?php

namespace RZP\Models\P2p\Client;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Device\Entity as DeviceEntity;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    protected $entity = 'p2p_client';

    public function getGatewayConfig(array $input): array
    {
        $this->initialize(Action::GET_GATEWAY_CONFIG, $input, true);

        $this->gatewayInput->put(DeviceEntity::CONTACT, $input[DeviceEntity::CONTACT]);

        return $this->callGateway();
    }

    public function getGatewayConfigSuccess(array $input): array
    {
        $this->initialize(Action::GET_GATEWAY_CONFIG_SUCCESS, $input, true);

        return $input;
    }

}
