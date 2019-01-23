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

        $this->repo->save($entity);

        $transaction->upi()->setModel($entity);

        return $entity;
    }

    public function update(Transaction\Entity $transaction, array $input): Entity
    {
        $transaction->upi->edit($input);

        $this->repo->save($transaction->upi);

        return $transaction->upi;
    }
}
