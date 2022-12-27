<?php

namespace RZP\Models\P2p\Client;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Error\P2p\ErrorCode;

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

        $customer = (new Device\Core)->getDeviceCustomer($input[Entity::CUSTOMER_ID]);

        // If customer id does not have contact throw exception
        if($customer->getContact() === null)
        {
            throw new Exception\P2p\BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED);
        }

        $this->gatewayInput->put(Entity::CUSTOMER, $customer->toArray());

        return $this->callGateway();
    }

    public function getGatewayConfigSuccess(array $input): array
    {
        $this->initialize(Action::GET_GATEWAY_CONFIG_SUCCESS, $input, true);

        return $input;
    }

}
