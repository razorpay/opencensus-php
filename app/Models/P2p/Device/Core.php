<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\Customer;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
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
            Entity::CONTACT     => $input[Entity::CONTACT],
        ]);

        if ($existing)
        {
            $this->validateExistingDevice($existing, $customer);

            $existing->edit($input);

            // We are going to change the customer here
            $existing->customer()->associate($customer);

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

    public function reassignCustomer(array $input): Entity
    {
        return $this->repo->transaction(
            function() use ($input)
            {
                $customer = $this->getDeviceCustomer($input[Entity::CUSTOMER_ID], true);

                $device = $this->context()->getDevice();

                $previousDevices = $this->repo->fetchAllByCustomer($customer);

                $forced = boolval(array_get($input, 'forced'));

                $shouldReassign = false;

                // If no devices found for customer, its normal scenario
                if ($previousDevices->count() === 0)
                {
                    $shouldReassign = true;
                }
                // Now there are already devices for customer, implementation is same for now
                else if ($forced === true)
                {
                    $sharedCustomer = $this->fetchOrCreateSharedCustomer();

                    // We are changing customer id for previous device to
                    // Shared Customer ,Now when those devices tries to register,
                    // they will error of customer id already taken
                    $previousDevices->each(function(Entity $device) use ($sharedCustomer)
                    {
                        $device->customer()->associate($sharedCustomer);

                        // Simply fail the binding
                        $device->generateAuthToken();

                        $this->repo->saveOrFail($device);
                    });

                    $shouldReassign = true;
                }

                if ($shouldReassign === true)
                {
                    $device->customer()->associate($customer);

                    // We need to get device run binding again
                    $device->generateAuthToken();

                    $this->repo->saveOrFail($device);
                }

                return $device;
            });
    }

    protected function validateExistingDevice(Entity $device, Customer\Entity $customer)
    {
        if ($device->getCustomerId() !== $customer->getId())
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_DEVICE_BLONGED_TO_OTHER_CUSTOMER);
        }
    }

    protected function fetchOrCreateSharedCustomer()
    {
        $input = [
            Customer\Entity::CONTACT    => Customer\Entity::SHARED_CUSTOMER_CONTACT,
        ];

        // Failing on duplicate is disabled
        $customer = (new Customer\Core)->createLocalCustomer($input, $this->context()->getMerchant(), false);

        return $customer;
    }
}
