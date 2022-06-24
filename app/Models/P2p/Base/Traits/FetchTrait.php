<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Models\P2p\Device;
use RZP\Models\P2p\Device\Entity;
use RZP\Models\P2p\Base\Action;
use RZP\Models\P2p\Base\Libraries;
use RZP\Models\P2p\Beneficiary\Entity as Beneficiary;

/**
 * Trait MerchantTrait
 *
 * @package RZP\Models\P2p\Base\Traits
 */
trait FetchTrait
{

    public function fetchAll(array $input): array
    {
        // restrict fetch from merchant side , they have to pass device id for all the calls
        // strict validation will happen for merchant fetch to avoid bigger queries
        if($this->context()->getContextType() === Libraries\Context::MERCHANT)
        {
            $this->initialize(Action::FETCH_ALL, $input, true);

            $input[Entity::DEVICE_ID] = Entity::stripDefaultSign($this->input->get(Entity::DEVICE_ID));

            $device = (new Device\Core)->fetch(($this->input->get(Entity::DEVICE_ID)));

            $this->context()->setDevice($device);
        }
        // for other context existing validations will remain as it is
        else
        {
            $this->initialize(Action::FETCH_ALL, $input);
        }

        $entities = $this->core->fetchAll($input);

        return $entities->toArrayPublic();
    }

}
