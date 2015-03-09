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
        $repo = $this->getEntityRepository($entity);

        $repo = new $repo;

        $repo->setMerchantIdRequiredForMultipleFetch(false);

        $entity = $repo->fetch($input);

        return $entity->toArrayAdmin();
    }

    protected function getEntityNamespace($entity)
    {
        $ns = '';

        switch ($entity)
        {
            case 'refund':
                $ns = 'Models\Payment\Refund';
                break;

            case 'dailysettlement':
                $ns = 'Models\Settlement\Daily';
                break;

            default:
                $ns = 'Models\\'.ucfirst($entity);
        }

        return $ns;
    }

    protected function getEntityClass($entity)
    {
        return $this->getEntityNamespace($entity) . '\Entity';
    }

    protected function getEntityRepository($entity)
    {
        $namespace = $this->getEntityNamespace($entity);

        return $namespace.'\Repository';
    }
}