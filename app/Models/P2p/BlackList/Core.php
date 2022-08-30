<?php

namespace RZP\Models\P2p\BlackList;

use http\Exception\RuntimeException;
use RZP\Models\P2p\Base;

class Core extends Base\Core
{
    /**
     * This function is to create the blacklist
     * @param Base\Entity $blacklist
     * @param array $input
     * @return Entity
     * @throws \RZP\Exception\RuntimeException
     */
    public function create(Base\Entity $blacklist, array $input): Entity
    {
        $entity = $this->build($input);

        $existingEntity = $this->repo->findall([Entity::ENTITY_ID => $input[Entity::ENTITY_ID], Entity::TYPE => $input[Entity::TYPE]], true);

        // if entity does not exists we can add it to our database
        if($existingEntity === null)
        {
            $this->repo->saveOrFail($entity);

            return $entity;
        }

        //if the entity is existing for the same entity id and entity type , we can re nable the data back
        if($existingEntity !== null && $existingEntity[Entity::DELETED_AT] !== null)
        {
            $existingEntity->setDeletedAt(null);

            $this->repo->saveOrFail($existingEntity);

            return $existingEntity;
        }

        return $blacklist;
    }

    /**
     * This is the method to delete the blacklist entity
     * @param Entity $blacklist
     */
    public function delete(Entity $blacklist)
    {
        return $this->repo->deleteOrFail($blacklist);
    }

    /**
     * Get the first entity data from the list
     * @param array $input
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function findByEntityData(array $input)
    {
        return $this->repo->findAll([Entity::ENTITY_ID => $input[Entity::ENTITY_ID]] , false);
    }
}
