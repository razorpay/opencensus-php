<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'line_item';

    public function findByPublicIdAndMorphEntity($id, Base\Entity $morph)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndMorphEntity($id, $morph);
    }

    public function findByIdAndMorphEntity($id, Base\Entity $morph)
    {

        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $morph->getId())
                    ->where(Entity::ENTITY_TYPE, '=', $morph->getEntity())
                    ->findOrFailPublic($id);
    }
}
