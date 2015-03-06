<?php

namespace Models\Admin;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;

class Service extends Base\Service
{
    public function fetchEntityById($entity, $id)
    {
        $entityClass = $this->getEntityClass($entity);

        $id = $entityClass::verifyIdAndStripSign($id);

        $repo = $this->getEntityRepository($entity);

        $entity = (new $repo)->findOrFailPublic($id);

        return $entity->toArrayAdmin();
    }

    public function fetchMultipleEntities($entity, $input)
    {
        $repo = 'Models\\'.ucfirst($entity).'\Repository';

        $repo = new $repo;

        $repo->setMerchantIdRequiredForMultipleFetch(false);

        $entity = $repo->fetch($input);

        return $entity->toArrayAdmin();
    }

    protected function getEntityNamespace($entity)
    {
        return 'Models\\'.ucfirst($entity);
    }

    protected function getEntityClass($entity)
    {
        return 'Models\\'.ucfirst($entity).'\Entity';
    }

    protected function getEntityRepository($entity)
    {
        $namespace = $this->getEntityNamespace($entity);

        return $namespace.'\Repository';
    }
}