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

    public function __construct()
    {
        parent::__construct();
    }

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

            $this->repo->saveOrFail($address);

            if ($address->getPrimary() === true)
            {
                // We are passing the address ID here because we save the address in the previous step with
                // primary set to true. Hence, we will always get 1 or more primary addresses even if we are
                // creating a new address for the customer. We need to get primary addresses `except`
                // this address which is just created.
                $currentPrimaryAddress = $this->repo->address->fetchCurrentPrimaryAddress(
                    Type::CUSTOMER, $customer->getId(), $address->getAddressType(), $address->getId());

                $this->handlePrimaryAddressSwitch($currentPrimaryAddress, $customer);

                // We can't use associations here because a customer's shipping address can be
                // associated with many addresses. Laravel will not understand which one to associate it with.
                // TODO: Check if there's a better way of doing this than just setting.
                // TODO: Make this dynamic.
                $customer->setShippingAddressId($address->getId());
            }

            $this->repo->saveOrFail($customer);
        });

        return $address;
    }

    protected function handlePrimaryAddressSwitch($currentPrimaryAddress, $customer)
    {
        if ($currentPrimaryAddress->count() > 1)
        {
            throw new LogicException(
                'Found multiple primary addresses.',
                null,
                [
                    'entity_id'     => $customer->getId(),
                    'entity_type'   => Type::CUSTOMER,
                ]
            );
        }

        if ($currentPrimaryAddress->count() === 1)
        {
            $currentPrimaryAddress = $currentPrimaryAddress->first();

            $currentPrimaryAddress->setPrimary(false);

            $this->repo->saveOrFail($currentPrimaryAddress);
        }

        // If there is no current primary address, there's no need to do anything
    }
}