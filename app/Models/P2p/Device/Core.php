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
        // First we will try to find the device for same sim,
        // merchant and app name. Here we will check for UUID.
        $existing = $this->repo->findByDeviceProperties(array_only($input, [
            Entity::SIMID,
            Entity::APP_NAME
        ]));

        // If device exists we will update the device details and regenerate the auth_token
        if ($existing)
        {
            $existing->edit($input);

            $existing->generateAuthToken();

            $this->repo->saveOrFail($existing);

            return $existing;
        }

        // This might need to be done inside a transaction
        $device = $this->repo->newP2pEntity();

        $device->build($input);

        $customer = $this->getDeviceCustomer($input[Entity::CUSTOMER_ID]);
        $device->customer()->associate($customer);

        $this->repo->saveOrFail($device);

        return $device;
    }

    protected function getDeviceCustomer(string $customerId): Customer\Entity
    {
        Customer\Entity::verifyIdAndStripSign($customerId);

        return $this->repo()->customer->findOrFailPublic($customerId);
    }
}
