<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Models;
use RZP\Gateway;
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
        'netbanking',
        'upi',
    ];

    protected function getDbEntities(string $entity, array $input = array(), $mode = 'test')
    {
        return $this->getEntityObjectForMode($entity, $mode)
                    ->where($input)
                    ->get();
    }

    protected function getTrashedDbEntities(string $entity, array $input = array(), $mode = 'test')
    {
        return $this->getEntityObjectForMode($entity, $mode)
                    ->where($input)
                    ->onlyTrashed()
                    ->get();
    }

    protected function getDbEntity(string $entity, array $input = array(), $mode = 'test')
    {
        return $this->getEntityObjectForMode($entity, $mode)
                    ->where($input)
                    ->get()
                    ->last();
    }

    protected function getTrashedDbEntity(string $entity, array $input = array(), $mode = 'test')
    {
        return $this->getEntityObjectForMode($entity, $mode)
                    ->where($input)
                    ->onlyTrashed()
                    ->get()
                    ->last();
    }

    protected function getDbLastEntityOrderByCreatedAt(string $entity, array $input = array(), $mode = 'test')
    {
        return $this->getEntityObjectForMode($entity, $mode)
            ->where($input)
            ->orderBy('created_at', 'desc')->first();
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

    protected function getTrashedDbEntityById($entity, $id, $mode = 'test')
    {
        $entityClass = $this->getEntityObjectForMode($entity, $mode);

        if (in_array($entity, $this->verificationSkipEntities) === false)
        {
            $id = $entityClass::verifyIdAndSilentlyStripSign($id);
        }

        return $entityClass->withTrashed()->findOrFailPublic($id);
    }

    protected function getDbLastPayment(): Models\Payment\Entity
    {
        return $this->getDbLastEntity('payment');
    }

    protected function getDbLastOrder(): Models\Order\Entity
    {
        return $this->getDbLastEntity('order');
    }

    protected function getDbLastOrderMeta(): Models\Order\OrderMeta\Entity
    {
        return $this->getDbLastEntity('order_meta');
    }

    protected function getDbLastUpi(): Gateway\Upi\Base\Entity
    {
        return $this->getDbLastEntity('upi');
    }

    protected function getDbLastRefund(): Models\Payment\Refund\Entity
    {
        return $this->getDbLastEntity('refund');
    }

    protected function getDbLastMozart(): Gateway\Mozart\Entity
    {
        return $this->getDbLastEntity('mozart');
    }

    private function getEntityObjectForMode($entity, $mode = 'test')
    {
        $entityObject =  Entity::getEntityObject($entity);

        $entityObject->setConnection($mode);

        return $entityObject;
    }

    protected function getDbEntitiesInOrder(
        string $entity, string $column, array $input = [], $sort = 'asc', $mode = 'test')
    {
        return $this->getEntityObjectForMode($entity, $mode)
                    ->where($input)
                    ->orderBy($column, $sort)
                    ->get();
    }
}
