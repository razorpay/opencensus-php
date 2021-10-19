<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Transaction;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(Transaction\Entity $transaction, array $input): Entity
    {
        $entity = $this->build($input);

        $entity->associateTransaction($transaction);

        $this->repo->saveOrFail($entity);

        $transaction->upi()->setModel($entity);

        return $entity;
    }

    public function update(Entity $upi, array $input): Entity
    {
        $upi->edit($input);

        $this->repo->saveOrFail($upi);

        return $upi;
    }

    public function findAll(array $input)
    {
        return $this->repo->findAll($input);
    }
}
