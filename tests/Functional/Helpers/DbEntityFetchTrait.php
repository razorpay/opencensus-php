<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Models;
use RZP\Constants\Entity;

trait DbEntityFetchTrait
{
    protected function getDbEntities(string $entity, array $input = array(), $mode = 'test')
    {
        return $this->getEntityClass($entity, $mode)
                    ->where($input)
                    ->get();
    }

    protected function getDbLastEntity($entity, $mode = 'test')
    {
        $entities = $this->getDbEntities($entity, [], $mode);

        if (count($entities) > 0)
        {
            return $entities[(count($entities) - 1)];
        }
    }

    protected function getDbLastEntityToArray($entity, $mode = 'test')
    {
        $lastEntity = $this->getDbLastEntity($entity, $mode);

        if (empty($lastEntity) === false)
        {
            return $lastEntity->toArray();
        }
    }

    protected function getDbEntityById($entity, $id, $mode = 'test')
    {
        $entityClass = $this->getEntityClass($entity, $mode);

        $id = $entityClass::verifyIdAndStripSign($id);

        return $entityClass->findOrFailPublic($id);
    }

    protected function getDbLastPayment(): Models\Payment\Entity
    {
        return $this->getDbLastEntity('payment');
    }

    protected function getDbLastRefund(): Models\Payment\Refund\Entity
    {
        return $this->getDbLastEntity('refund');
    }

    private function getEntityClass($entity, $mode = 'test')
    {
        $entityObject =  Entity::getEntityObject($entity);

        $entityObject->setConnection($mode);

        return $entityObject;
    }
}
