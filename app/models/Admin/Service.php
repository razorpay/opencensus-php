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

        $entities = $repo->fetch($input);

        return $entities->toArrayAdmin();
    }

    protected function getEntityNamespace($entity)
    {
        $map = array(
            'refund'            => 'Models\Payment\Refund',
            'dailysettlement'   => 'Models\Settlement\Daily',
            'atom'              => 'Gateway\Atom',
            'bank_account'      => 'Models\Merchant\BankAccount',
            'kotak'             => 'Gateway\Kotak',
            'axis_migs'         => 'Gateway\AxisMigs',
            'axis_genius'       => 'Gateway\AxisGenius',
            'paytm'             => 'Gateway\Paytm',
            'netbanking'        => 'Gateway\Netbanking',
        );

        if (array_key_exists($entity, $map))
        {
            return $map[$entity];
        }

        return 'Models\\'.ucfirst($entity);
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