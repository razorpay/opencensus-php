<?php

namespace RZP\Models\Customer\Address;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Customer;

class Core extends Base\Core
{
    // TODO: All the functions here need to be generic enough to handle any type of
    // entity and not only customer. These functions should be able to handle
    // merchant's address also in the same manner.

    public function create(Customer\Entity $customer, array $input)
    {
        // TODO: Should we limit the number of addresses that a particular
        // combination of {entity_id, entity_type, address_type} can be created for?
        // If we don't, someone can create thousands of addresses. Where do we have
        // a validation or a check? We are allowing any number of multiple addresses
        // for a customer. Since this is done from server side, someone may create
        // lots of addresses by mistake also. Maybe kept the api in a loop or something.
        // Do we need to worry about this or is it okay?


        // TODO: Call a different function inside the transaction and perform all the required actions there.

        $address = (new Entity)->build($input);

        $this->repo->transaction(function() use ($address, $customer)
        {
            $address->setEntityType(Type::CUSTOMER);

            $address->customer()->associate($customer);

            // This needs to be saved here so that handlePrimaryAddressSwitch()
            // can retrieve the customer by association if required.
            $this->repo->saveOrFail($address);

            if ($address->getPrimary() === true)
            {
                $this->handlePrimaryAddressSwitch($address);
            }
        });

        return $address;
    }

    public function setPrimaryAddress(Entity $address)
    {
        $this->handlePrimaryAddressSwitch($address);

        return $address;
    }

    public function delete(Entity $address, Customer\Entity $customer)
    {
        return $this->repo->transaction(function() use ($address, $customer)
        {
            if ($address->getPrimary() === true)
            {
                // Since this address is going to be deleted, the address cannot be primary any more.
                $address->setPrimary(false);

                $this->repo->saveOrFail($address);

                // We are passing the address ID here because we want the latest address, excluding the current one
                // since we are going to delete this one.
                $latestAddress = $this->repo->address->fetchLatestAddress(
                    Type::CUSTOMER, $customer->getId(), $address->getAddressType(), $address->getId());

                if ($latestAddress !== null)
                {
                    $latestAddress->setPrimary(true);

                    $this->repo->saveOrFail($latestAddress);

                    $addressId = $latestAddress->getId();
                }
                else
                {
                    // If $latestAddress is null, there's nothing to do. It just means that there was just
                    // one address which we are going to delete.

                    $addressId = null;
                }

                // TODO: Use type also to figure what value to set null.
                $customer->setShippingAddressId($addressId);

                $this->repo->saveOrFail($customer);
            }

            return $this->repo->address->deleteOrFail($address);
        });
    }

    protected function handlePrimaryAddressSwitch(Entity $address)
    {
        $customer = $address->customer;

        // We are passing the address ID here because we save the address in the previous step with
        // primary set to true. Hence, we will always get 1 or more primary addresses even if we are
        // creating a new address for the customer. We need to get primary addresses `except`
        // this address which is just created.
        $currentPrimaryAddress = $this->repo->address->fetchCurrentPrimaryAddress(
            Type::CUSTOMER, $customer->getId(), $address->getAddressType(), $address->getId());

        if ($currentPrimaryAddress->count() > 1)
        {
            throw new LogicException(
                'Found multiple primary addresses for an address type.',
                null,
                [
                    'entity_id'     => $customer->getId(),
                    'entity_type'   => Type::CUSTOMER,
                    'address_type'  => $address->getAddressType(),
                ]
            );
        }

        $this->repo->transaction(function() use ($currentPrimaryAddress, $address, $customer)
        {
            // If there is no current primary address, there's no need to do anything

            if ($currentPrimaryAddress->count() === 1)
            {
                $currentPrimaryAddress = $currentPrimaryAddress->first();

                $currentPrimaryAddress->setPrimary(false);

                $address->setPrimary(true);

                $this->repo->saveOrFail($currentPrimaryAddress);

                $this->repo->saveOrFail($address);
            }

            // We can't use associations here because a customer's shipping address can be
            // associated with many addresses. Laravel will not understand which one to associate it with.
            // TODO: Check if there's a better way of doing this than just setting.
            // TODO: Make this dynamic.
            $customer->setShippingAddressId($address->getId());

            $this->repo->saveOrFail($customer);
        });
    }
}