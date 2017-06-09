<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'line_item';

    public function findByPublicIdAndMorphEntity(
        string $id,
        Base\PublicEntity $morphEntity): Entity
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndMorphEntityOrFail($id, $morphEntity);
    }

    public function findByIdAndMorphEntityOrFail(
        string $id,
        Base\PublicEntity $morphEntity): Entity
    {
        return $this->newQuery()
                    ->entity($morphEntity)
                    ->findOrFailPublic($id);
    }

    public function findManyByPublicIdsAndMorphEntity(
        array $ids,
        Base\PublicEntity $morphEntity): Base\PublicCollection
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSignMultiple($ids);

        return $this->findManyByIdsAndMorphEntity($ids, $morphEntity);
    }

    public function findManyByIdsAndMorphEntity(
        array $ids,
        Base\PublicEntity $morphEntity): Base\PublicCollection
    {
        return $this->newQuery()
                    ->entity($morphEntity)
                    ->findManyOrFailPublic($ids);
    }
}
