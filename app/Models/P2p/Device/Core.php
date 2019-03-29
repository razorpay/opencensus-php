<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\Customer;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function createOrUpdate(array $input): Entity
    {
        // First we will make sure that customer exists and belongs to same merchant
        $customer = $this->getDeviceCustomer($input[Entity::CUSTOMER_ID], false);

        // Then we will check if there are devices already created for customer
        $existing = $this->repo->findByDeviceProperties([
            Entity::CUSTOMER_ID => $customer->getId(),
            Entity::CONTACT     => $input[Entity::CONTACT],
        ]);

        if ($existing)
        {
            $existing->edit($input);

            // This will make sure older device doesn't work
            $existing->generateAuthToken();

            $this->repo->saveOrFail($existing);

            return $existing;
        }

        // This might need to be done inside a transaction
        $device = $this->repo->newP2pEntity();

        $device->build($input);

        $device->customer()->associate($customer);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function getDeviceCustomer(string $customerId, bool $signed = true): Customer\Entity
    {
        if ($signed === true)
        {
            Customer\Entity::verifyIdAndStripSign($customerId);
        }

        return $this->repo()->customer->findByIdAndMerchant($customerId, $this->context()->getMerchant());
    }
}
