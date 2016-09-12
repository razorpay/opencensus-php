<?php

namespace RZP\Models\Address;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * Maximum number of addresses allowed for a particular address type for an entity (customer/merchant)
     */
    const MAX_ALLOWED_ADDRESSES = 3;

    /**
     * Builds a new address entity. Associates this address with the customer which is sent in the input.
     * If this address is set to be the primary address, we switch it with the previous primary address, if present.
     * If not, we don't do anything. We just create the address and the association with the customer.
     *
     * @param Customer\Entity $customer
     * @param array $input
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(Customer\Entity $customer, array $input)
    {
        $this->trace->info(
            TraceCode::ADDRESS_CREATE_REQUEST,
            $input
        );

        $address = (new Entity)->build($input);

        $currentAddresses = $this->repo->address->fetchAddressesForEntity(
            Type::CUSTOMER, $customer->getId(), [Entity::ADDRESS_TYPE => $input[Entity::ADDRESS_TYPE]]);

        if ($currentAddresses->count() >= self::MAX_ALLOWED_ADDRESSES)
        {
            throw new Exception\BadRequestValidationFailureException(
                'You cannot have more than ' . self::MAX_ALLOWED_ADDRESSES . ' ' .
                $input[Entity::ADDRESS_TYPE] . ' for ' . Type::CUSTOMER);
        }

        return $this->repo->transaction(function() use ($address, $customer)
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

            return $address;
        });
    }

    public function setPrimaryAddress(Entity $address)
    {
        $address->setPrimary(true);
        $this->repo->saveOrFail($address);

        $this->handlePrimaryAddressSwitch($address);

        return $address;
    }

    /**
     * We check whether the address that needs to be deleted in primary.
     * If it's not, we just delete it and return.
     * If it is,
     *  - we set the primary flag to false
     *  - get the latest address from the db, excluding the address that is being deleted from the query
     *  - if the above query returns 0 results (there's only one address, the one being deleted),
     *    we set the customer's address to null.
     *  - else, we set the latest address to primary and update the customer's address attribute.
     *
     * @param Entity $address The address entity which needs to be deleted
     * @return mixed
     */
    public function delete(Entity $address)
    {
        $customer = $address->customer;

        $this->trace->info(
            TraceCode::ADDRESS_DELETE_REQUEST,
            [
                'address_id'    => $address->getId(),
                'address_type'  => $address->getAddressType(),
                'customer_id'   => $customer->getId(),
            ]
        );

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

                $setterFunc = $this->getSetterFunctionForAddress($address->getAddressType());
                $customer->$setterFunc($addressId);

                $this->repo->saveOrFail($customer);
            }

            return $this->repo->address->deleteOrFail($address);
        });
    }

    /**
     * Sets the passed address to primary and saves it.
     * Gets the current primary address (excluding the passed address).
     * If there is no current primary address, we don't do anything.
     * If there is a current primary address,
     *   - set its primary flag to false.
     * Irrespective of current primary address being present or not,
     * we set the associated customer's address ID to the passed address's ID.
     *
     * @param Entity $address The address entity which we need to set as primary,
     *                        displacing the older primary address.
     * @throws Exception\LogicException
     */
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
            throw new Exception\LogicException(
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

            // Since we are switching the passed address to primary, we mark it as primary and save the address.
            $address->setPrimary(true);
            $this->repo->saveOrFail($address);

            if ($currentPrimaryAddress->count() === 1)
            {
                $currentPrimaryAddress = $currentPrimaryAddress->first();

                $currentPrimaryAddress->setPrimary(false);

                $this->repo->saveOrFail($currentPrimaryAddress);

                $this->trace->info(
                    TraceCode::PRIMARY_ADDRESS_SWITCH,
                    [
                        'customer_id'           => $customer->getId(),
                        'address_type'          => $address->getAddressType(),
                        'old_primary_address'   => $currentPrimaryAddress->getId(),
                        'new_primary_address'   => $address->getId(),
                    ]
                );
            }

            // TODO: Check if there's a better way of doing this than just setting.

            // We can't use associations here because a customer's shipping address can be
            // associated with many addresses. Laravel will not understand which one to associate it with.

            $setterFunc = $this->getSetterFunctionForAddress($address->getAddressType());
            $customer->$setterFunc($address->getId());

            $this->repo->saveOrFail($customer);
        });
    }

    protected function getSetterFunctionForAddress($addressType)
    {
        return 'set' . studly_case($addressType) . 'Id';
    }
}