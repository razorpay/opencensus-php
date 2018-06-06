<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Models;
use RZP\Constants\Entity;

trait DbEntityFetchTrait
{
    /**
     * Since $generateIdOnCreate is a protected variable, we can not
     * access it to check if verification is needed for an entity.
     *
     * Hence, we keep a list of entities here to check this manually.
     */
    protected $verificationSkipEntities = [
        'netbanking'
    ];

    protected function getDbEntities(string $entity, array $input = array(), $mode = 'test')
    {
        return $this->getEntityObjectForMode($entity, $mode)
                    ->where($input)
                    ->get();
    }

    protected function getDbLastEntity($entity, $mode = 'test')
    {
        $entities = $this->getDbEntities($entity, [], $mode);

        return $entities->last();
    }

    protected function getDbLastEntityToArray($entity, $mode = 'test')
    {
        $lastEntity = $this->getDbLastEntity($entity, $mode);

        return $lastEntity ? $lastEntity->toArray() : [];
    }

    protected function getDbLastEntityPublic($entity, $mode = 'test')
    {
        $lastEntity = $this->getDbLastEntity($entity, $mode);

        return $lastEntity ? $lastEntity->toArrayAdmin() : [];
    }

    protected function getDbEntityById($entity, $id, $mode = 'test')
    {
        $entityClass = $this->getEntityObjectForMode($entity, $mode);

        if (in_array($entity, $this->verificationSkipEntities) === false)
        {
            $id = $entityClass::verifyIdAndSilentlyStripSign($id);
        }

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

    private function getEntityObjectForMode($entity, $mode = 'test')
    {
        $entityObject =  Entity::getEntityObject($entity);

        $entityObject->setConnection($mode);

        return $entityObject;
    }
}
