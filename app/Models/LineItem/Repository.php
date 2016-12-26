<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'line_item';

    public function findByPublicIdAndMorphEntity($id, Base\Entity $morphEntity)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndMorphEntity($id, $morphEntity);
    }

    public function findByIdAndMorphEntity($id, Base\Entity $morphEntity)
    {

        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $morphEntity->getId())
                    ->where(Entity::ENTITY_TYPE, '=', $morphEntity->getEntity())
                    ->findOrFailPublic($id);
    }

    public function findManyByPublicIdsAndMorphEntity($ids, Base\Entity $morphEntity)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSignMultiple($ids);

        return $this->findManyByIdsAndMorphEntity($ids, $morphEntity);
    }

    public function findManyByIdsAndMorphEntity($ids, Base\Entity $morphEntity)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $morphEntity->getId())
                    ->where(Entity::ENTITY_TYPE, '=', $morphEntity->getEntity())
                    ->findMany($ids);
    }
}
