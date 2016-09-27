<?php

namespace RZP\Models\Address;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * Maximum number of addresses allowed for a particular address type for an entity (customer/merchant)
     */
    const MAX_ALLOWED_ADDRESSES = 3;

    /**
     * Builds a new address entity. Associates this address with the entity which is sent in the input.
     * If this address is set to be the primary address, we switch it with the previous primary address, if present.
     * If not, we don't do anything. We just create the address and the association with the entity.
     *
     * @param Base\Entity $entity
     * @param $entityType
     * @param array $input
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(Base\Entity $entity, $entityType, array $input)
    {
        $this->trace->info(
            TraceCode::ADDRESS_CREATE_REQUEST,
            $input);

        $address = (new Entity)->build($input);

        $currentAddresses = $this->repo->address->fetchAddressesForEntity(
            $entity, [Entity::TYPE => $input[Entity::TYPE]]);

        if ($currentAddresses->count() >= self::MAX_ALLOWED_ADDRESSES)
        {
            throw new Exception\BadRequestValidationFailureException(
                'You cannot have more than ' . self::MAX_ALLOWED_ADDRESSES . ' ' .
                $input[Entity::TYPE] . ' for ' . $entityType);
        }

        return $this->repo->transaction(function() use ($address, $entity)
        {
            $address->sourceAssociate($entity);

            if ($address->isPrimary() === true)
            {
                $this->handlePrimaryAddressSwitch($address);
            }

            $this->repo->saveOrFail($address);

            return $address;
        });
    }

    public function setPrimaryAddress(Entity $address)
    {
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
     *    we set the entity's address to null.
     *  - else, we set the latest address to primary and update the entity's address attribute.
     *
     * @param Entity $address The address entity which needs to be deleted
     * @return mixed
     */
    public function delete(Entity $address)
    {
        $entity = $address->source()->getRelated();

        $this->trace->info(
            TraceCode::ADDRESS_DELETE_REQUEST,
            [
                'address_id'    => $address->getId(),
                'type'          => $address->getType(),
                'entity_id'     => $entity->getId(),
            ]);

        return $this->repo->transaction(function() use ($address, $entity)
        {
            if ($address->isPrimary() === true)
            {
                // Since this address is going to be deleted, the address cannot be primary any more.
                $address->setPrimary(false);

                $this->repo->saveOrFail($address);

                // We are passing the address ID here because we want the latest address, excluding the current one
                // since we are going to delete this one.
                $latestAddress = $this->repo->address->fetchLatestAddressForEntity($entity, $address, true);

                if ($latestAddress !== null)
                {
                    $latestAddress->setPrimary(true);

                    $this->repo->saveOrFail($latestAddress);
                }
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
     * we set the associated entity's address ID to the passed address's ID.
     *
     * @param Entity $address The address entity which we need to set as primary,
     *                        displacing the older primary address.
     * @throws Exception\LogicException
     */
    protected function handlePrimaryAddressSwitch(Entity $address)
    {
        $entity = $address->source()->getRelated();

        $currentPrimaryAddress = $this->repo->address->fetchCurrentPrimaryAddressOfEntity($entity, $address);

        if ($currentPrimaryAddress->count() > 1)
        {
            throw new Exception\LogicException(
                'Found multiple primary addresses for an address type.',
                null,
                [
                    'entity_id'   => $entity->getId(),
                    'entity_type' => $address->getEntityType(),
                    'type'        => $address->getType(),
                ]);
        }

        $this->repo->transaction(function () use ($currentPrimaryAddress, $address, $entity)
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
                    TraceCode::ADDRESS_PRIMARY_SWITCH,
                    [
                        'entity_id'           => $entity->getId(),
                        'entity_type'         => $address->getEntityType(),
                        'type'                => $address->getType(),
                        'old_primary_address' => $currentPrimaryAddress->getId(),
                        'new_primary_address' => $address->getId(),
                    ]);
            }
        });
    }
}